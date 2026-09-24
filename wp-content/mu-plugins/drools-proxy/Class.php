<?php

namespace DroolsProxy;

class DroolsProxy {
  const SCREENING_TOKEN_TRANSIENT = 'drools_proxy_screening_api_token';
  const TOKEN_REFRESH_BUFFER_SECONDS = 300;

  /**
   * Add AJAX action for logged in and non-logged in users.
   */
  public function __construct() {
    add_action('wp_ajax_drools', [$this, 'incoming']);
    add_action('wp_ajax_nopriv_drools', [$this, 'incoming']);
  }

  /**
   * The request handler for eligibility screening via Screening API.
   */
  public function incoming() {
    $config = $this->getScreeningApiConfig();

    if (empty($config['base_url']) || empty($config['user']) || empty($config['pass'])) {
      $this->notify(__('Screening API configuration is missing information.'), true);

      wp_send_json([
        'status' => 'fail',
        'message' => 'invalid configuration'
      ], 412);

      wp_die();
    }

    if (!isset($_POST['data'])) {
      wp_send_json([
        'type' => 'FAILURE',
        'errors' => [['message' => 'Missing screener submission data']]
      ], 400);

      wp_die();
    }

    $uid = uniqid();
    $payload = wp_unslash($_POST['data']);

    do_action('drools_request', $payload, $uid);

    $response = $this->requestScreeningApiEligibility($config, $payload);

    if ($response['error']) {
      $this->notify($response['error'], true);

      wp_send_json(
        $response['body'] ?: ['type' => 'FAILURE', 'errors' => [['message' => $response['error']]]],
        $response['code'] ?: 500
      );

      wp_die();
    }

    $ret = $response['body'];

    if (!is_object($ret)) {
      $ret = json_decode(wp_json_encode($ret));
    }

    do_action('drools_response', $ret, $uid);

    $ret->GUID = $uid;

    wp_send_json($ret, 200);
    wp_die();
  }

  /**
   * @return array{base_url:string,user:string,pass:string}
   */
  private function getScreeningApiConfig() {
    $base_url = get_option('screening_api_base_url');
    $user = get_option('screening_api_user');
    $pass = get_option('screening_api_pass');

    if (empty($base_url) && defined('SCREENING_API_BASE_URL')) {
      $base_url = SCREENING_API_BASE_URL;
    }
    if (empty($user) && defined('SCREENING_API_USER')) {
      $user = SCREENING_API_USER;
    }
    if (empty($pass) && defined('SCREENING_API_PASS')) {
      $pass = SCREENING_API_PASS;
    }

    return [
      'base_url' => rtrim((string) $base_url, '/'),
      'user' => (string) $user,
      'pass' => (string) $pass,
    ];
  }

  /**
   * @param array{base_url:string,user:string,pass:string} $config
   * @param mixed $payload
   * @return array{error:?string,code:?int,body:?object}
   */
  private function requestScreeningApiEligibility($config, $payload, $allowRetry = true) {
    $tokenResponse = $this->getScreeningApiToken($config, false);

    if (!empty($tokenResponse['error'])) {
      return [
        'error' => $tokenResponse['error'],
        'code' => 500,
        'body' => null,
      ];
    }

    $url = $config['base_url'] . '/eligibilityPrograms';
    $body = wp_json_encode($payload);

    $httpResponse = $this->httpPost($url, $body, [
      'Content-Type: application/json',
      'Authorization: ' . $tokenResponse['token'],
    ], 30);

    if ($allowRetry && $this->isTransientEligibilityHttpCode($httpResponse['code'])) {
      return $this->requestScreeningApiEligibility($config, $payload, false);
    }

    if ($httpResponse['body'] === false || $httpResponse['body'] === '') {
      return [
        'error' => __('The Screening API response is false or empty'),
        'code' => $httpResponse['code'] ?: 500,
        'body' => null,
      ];
    }

    $decoded = json_decode($httpResponse['body']);

    if ($httpResponse['code'] >= 400) {
      return [
        'error' => __('The Screening API request failed, response code ') . $httpResponse['code'],
        'code' => $httpResponse['code'],
        'body' => $decoded,
      ];
    }

    return [
      'error' => null,
      'code' => $httpResponse['code'],
      'body' => $decoded,
    ];
  }

  /**
   * HTTP status codes where a single immediate retry may succeed (gateway / upstream blips).
   *
   * @param int $code
   * @return bool
   */
  private function isTransientEligibilityHttpCode($code) {
    return in_array((int) $code, [408, 502, 503, 504], true);
  }

  /**
   * @param array{base_url:string,user:string,pass:string} $config
   * @param bool $forceRefresh
   * @return array{token:?string,error:?string}
   */
  private function getScreeningApiToken($config, $forceRefresh = false) {
    if (!$forceRefresh) {
      $cached = get_transient(self::SCREENING_TOKEN_TRANSIENT);

      if (is_array($cached) && !empty($cached['token']) && !empty($cached['expires_at'])) {
        if ((int) $cached['expires_at'] > (time() + self::TOKEN_REFRESH_BUFFER_SECONDS)) {
          return ['token' => $cached['token'], 'error' => null];
        }
      }
    }

    $auth = $this->fetchScreeningApiToken($config);

    if (!empty($auth['error'])) {
      return $auth;
    }

    $expires_at = time() + 3300;
    set_transient(self::SCREENING_TOKEN_TRANSIENT, [
      'token' => $auth['token'],
      'expires_at' => $expires_at,
    ], 3300);

    return $auth;
  }

  /**
   * @param array{base_url:string,user:string,pass:string} $config
   * @return array{token:?string,error:?string}
   */
  private function fetchScreeningApiToken($config) {
    $url = $config['base_url'] . '/authToken';
    $body = wp_json_encode([
      'username' => $config['user'],
      'password' => $config['pass'],
    ]);

    $response = $this->httpPost($url, $body, [
      'Content-Type: application/json',
    ], 30);

    if ($response['body'] === false || $response['body'] === '') {
      return [
        'token' => null,
        'error' => __('Screening API authToken returned an empty response'),
      ];
    }

    $decoded = json_decode($response['body']);

    if ($response['code'] >= 400 || !is_object($decoded)) {
      return [
        'token' => null,
        'error' => __('Screening API authToken failed with response code ') . $response['code'],
      ];
    }

    if (!isset($decoded->type) || $decoded->type !== 'SUCCESS' || empty($decoded->token)) {
      return [
        'token' => null,
        'error' => __('Screening API authToken did not return a success token'),
      ];
    }

    return [
      'token' => $decoded->token,
      'error' => null,
    ];
  }

  /**
   * @param string $url
   * @param string $body
   * @param string[] $headers
   * @param int $timeout
   * @return array{code:int,body:string|false}
   */
  private function httpPost($url, $body, $headers, $timeout = 15) {
    $ch = curl_init();

    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_TIMEOUT => $timeout,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $body,
      CURLOPT_FRESH_CONNECT => true,
      CURLOPT_HTTPHEADER => $headers,
    ]);

    $responseBody = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    curl_close($ch);

    return [
      'code' => $code,
      'body' => $responseBody,
    ];
  }

  /**
   * Create the settings for the eligibility proxy plugin.
   */
  public function createSettingsSection() {
    add_settings_section(
      'drools_proxy',
      'Screening API Settings',
      '<p>Enter Screening API credentials for the ACCESS NYC eligibility screener.</p>',
      'drools_config'
    );

    add_settings_field(
      'screening_api_base_url',
      'Screening API Base URL',
      [$this, 'settingsFieldHtml'],
      'drools_config',
      'drools_proxy',
      array(
        'id' => 'screening_api_base_url',
        'placeholder' => '',
        'private' => false
      )
    );

    add_settings_field(
      'screening_api_user',
      'Screening API Username',
      [$this, 'settingsFieldHtml'],
      'drools_config',
      'drools_proxy',
      array(
        'id' => 'screening_api_user',
        'placeholder' => '',
        'private' => false
      )
    );

    add_settings_field(
      'screening_api_pass',
      'Screening API Password',
      [$this, 'settingsFieldHtml'],
      'drools_config',
      'drools_proxy',
      array(
        'id' => 'screening_api_pass',
        'placeholder' => '',
        'private' => true
      )
    );

    add_settings_field(
      'drools_notify',
      'Notify',
      [$this, 'settingsFieldCheckbox'],
      'drools_config',
      'drools_proxy',
      array(
        'id' => 'drools_notify',
        'value' => '5',
        'label' => 'Check to notify the admin if there is an
          error. This will be disabled on the first instance of an error,
          however, all errors are logged.',
        'disabled' => false
      )
    );

    register_setting('drools_settings', 'screening_api_base_url');
    register_setting('drools_settings', 'screening_api_user');
    register_setting('drools_settings', 'screening_api_pass');
    register_setting('drools_settings', 'drools_notify');
  }

  /**
   * WordPress settings field template function for input fields
   *
   * @param  Array  $args  An array containing [privacy, input ID, and placeholder text] for the input
   */
  public function settingsFieldHtml($args) {
    echo implode('', [
      '<input ',
      ($args['private']) ? 'type="password" ' : 'type="text" ',
      'size="40" ',
      'name="' . $args['id'] . '" ',
      'id="' . $args['id'] . '" ',
      'value="' . get_option($args['id'], '') . '" ',
      'placeholder="' . __($args['placeholder']) . '" ',
      '/>'
    ]);

    $constantName = $this->settingsConstantName($args['id']);
    if ($constantName && defined($constantName)) {
      $constant = constant($constantName);
      $html = $constant;
      $html = ($args['private']) ? str_repeat('•', strlen($constant)) : $constant;

      echo implode('', [
        '<p class="description">',
        __('Environment currently set to '),
        '<code>' . $html . '</code>',
        '<p>'
      ]);
    }
  }

  /**
   * Maps a settings option id to an environment constant name.
   *
   * @param string $optionId
   * @return string|null
   */
  private function settingsConstantName($optionId) {
    $map = [
      'screening_api_base_url' => 'SCREENING_API_BASE_URL',
      'screening_api_user' => 'SCREENING_API_USER',
      'screening_api_pass' => 'SCREENING_API_PASS',
      'drools_notify' => 'DROOLS_NOTIFY',
    ];

    return isset($map[$optionId]) ? $map[$optionId] : strtoupper($optionId);
  }

  /**
   * Callback for add_settings_field. Creates the checkbox markup for a given admin option.
   * @link https://developer.wordpress.org/reference/functions/add_settings_field/
   *
   * @param   [type]  $args  Field arguments [ID, Value, Label, Disabled], of the text input
   */
  public function settingsFieldCheckbox($args) {
    echo implode('', [
      '<fieldset>',
      '  <legend class="screen-reader-text"><span>' . __($args['label']) . '</span></legend>',
      '  <label for="' . $args['id'] . '">',
      '    <input type="checkbox" value="' . $args['value'] . '" name="' . $args['id'] . '" id="' . $args['id'] . '" ',
      '    ' . checked($args['value'], get_option($args['id']), false) . ' ',
      '    ' . disabled($args['disabled'], true, false) . ' >',
      '    ' . __($args['label']) . '',
      '  </label>',
      '</fieldset>',
    ]);

    if (defined(strtoupper($args['id']))) {
      echo implode('', [
        '<p class="description">',
        __('Environment currently set to '),
        '<code>' . constant(strtoupper($args['id'])) . '</code>',
        '<p>'
      ]);
    }
  }

  /**
   * Logs a message and sends notification via wp_mail email. The admin will need
   * to reset the notify option once a message is sent to prevent continuous mailing.
   *
   * @param  String  $msg  The message to send.
   */
  public function notify($msg, $mail = false, $throttle = true) {
    $msg = 'Drools Proxy: ' . $msg;

    $notify = get_option('drools_notify');

    error_log($msg);

    if ($mail && $throttle && $notify === '5') {
      $msg = $msg . __(' This is the first instance of the error. All ' .
      'following instances will be logged to the server. Recheck the "Send ' .
      'Notifications" option in the admin menu.');

      wp_mail(get_option('admin_email'), 'Drools Proxy', $msg);

      update_option('drools_notify', '0');
    } elseif ($mail && !$throttle) {
      wp_mail(get_option('admin_email'), 'Drools Proxy', $msg);
    }
  }
}
