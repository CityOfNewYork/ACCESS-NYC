<?php

namespace DroolsProxy;

class DroolsProxy {
  /**
   * Add AJAX action for logged in and non-logged in users.
   */
  public function __construct() {
    add_action('wp_ajax_drools', [$this, 'incoming']);
    add_action('wp_ajax_nopriv_drools', [$this, 'incoming']);
  }

  /**
   * The request handler for the Drools Engine
   */
  public function incoming() {
    $url = get_option('drools_url');
    $user = get_option('drools_user');
    $pass = get_option('drools_pass');

    $url = (!empty($url)) ? $url : DROOLS_URL;
    $user = (!empty($user)) ? $user : DROOLS_USER;
    $pass = (!empty($pass)) ? $pass : DROOLS_PASS;

    if (empty($url) || empty($user) || empty($pass)) {
      $this->notify(__('The configuration is missing information.'), true);

      wp_send_json([
        'status' => 'fail',
        'message' => 'invalid configuration'
      ], 412);

      wp_die();
    }

    $uid = uniqid();

    do_action('drools_request', $_POST['data'], $uid);

    $response = $this->request($url, json_encode($_POST['data']), $user, $pass);

    if ($response === false || empty($response)) {
      $this->notify(__('The response is false or empty,') . ' "' .
        var_export($response, true) . '"', true);

      wp_send_json(['status' => 'fail'], 500);

      wp_die();
    }

    $ret = json_decode($response);

    do_action('drools_response', $ret, $uid);

    $ret->GUID = $uid;

    wp_send_json($ret, 200);
    wp_die();
  }

  /**
   * A wrapper around the PHP CURL request to the Drools Engine.
   *
   * @param   String  $url    The API endpoint of the Drools application
   * @param   JSON    $data   A JSON encoded Drools Object
   * @param   String  $user   The Drools account username
   * @param   String  $pass   The Drools account password
   *
   * @return  String/Boolean  The Drools response or false if request failed
   */
  private function request($url, $data, $user, $pass) {
    $ch = curl_init();

    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 3,
      CURLOPT_TIMEOUT => 5,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $data,
      CURLOPT_USERPWD => $user.":".$pass,
      CURLOPT_FRESH_CONNECT => true,
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "X-KIE-ContentType: json",
        "Content-Length: " . strlen($data)
      ]
    ]);

    $response = curl_exec($ch);

    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    if ($code >= 400) {
      $this->notify(__('The request failed, response code ') . $code  . '.', true);

      wp_send_json(['status' => 'fail'], $code);

      curl_close($ch);

      wp_die();
    }

    curl_close($ch);

    return $response;
  }

  /**
   * Create the settings for the Drools Proxy plugin
   */
  public function createSettingsSection() {
    add_settings_section(
      'drools_proxy',
      'Drools Settings',
      '<p>Enter your Drools credentials here.</p>',
      'drools_config'
    );

    add_settings_field(
      'drools_url',                 // field name
      'Drools Endpoint (URL)',      // label
      [$this, 'settingsFieldHtml'], // HTML content
      'drools_config',              // page
      'drools_proxy',               // section
      array(
        'id' => 'drools_url',
        'placeholder' => '',
        'private' => false
      )
    );

    add_settings_field(
      'drools_user',
      'User',
      [$this, 'settingsFieldHtml'],
      'drools_config',
      'drools_proxy',
      array(
        'id' => 'drools_user',
        'placeholder' => '',
        'private' => false
      )
    );

    add_settings_field(
      'drools_pass',
      'Password',
      [$this, 'settingsFieldHtml'],
      'drools_config',
      'drools_proxy',
      array(
        'id' => 'drools_pass',
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

    register_setting('drools_settings', 'drools_url');
    register_setting('drools_settings', 'drools_user');
    register_setting('drools_settings', 'drools_pass');
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

    if (defined(strtoupper($args['id']))) {
      $constant = constant(strtoupper($args['id']));
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
      echo implode([
        '<p class="description">',
        __('Environment currently set to '),
        '<code>' . constant(strtoupper($args['id'])) . '</code>',
        '<p>'
      ], '');
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
