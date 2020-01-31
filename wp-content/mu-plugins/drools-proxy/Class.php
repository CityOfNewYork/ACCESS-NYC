<?php

namespace DroolsProxy;

class DroolsProxy {
  /**
   * [__construct description]
   */
  public function __construct() {
    add_action('wp_ajax_drools', [$this, 'incoming']);
    add_action('wp_ajax_nopriv_drools', [$this, 'incoming']);
  }

  /**
   * [incoming description]
   */
  public function incoming() {
    $url = get_option('drools_url');
    $user = get_option('drools_user');
    $pass = get_option('drools_pass');

    $url = (!empty($url)) ? $url : DROOLS_URL;
    $user = (!empty($user)) ? $user : DROOLS_USER;
    $pass = (!empty($pass)) ? $pass : DROOLS_PASS;

    if (empty($url) || empty($user) || empty($pass)) {
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
      wp_send_json([ 'status' => 'fail'], 500);
      wp_die();
    }

    $ret = json_decode($response);

    do_action('drools_response', $ret, $uid);

    $ret->GUID = $uid;

    wp_send_json($ret, 200);
    wp_die();
  }

  /**
   * [request description]
   * @param   [type]  $url   [$url description]
   * @param   [type]  $data  [$data description]
   * @param   [type]  $user  [$user description]
   * @param   [type]  $pass  [$pass description]
   * @return  [type]         [return description]
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
    curl_close($ch);

    return $response;
  }

  /**
   * [createSettingsSection description]
   */
  public function createSettingsSection() {
    add_settings_section('drools_proxy', 'Drools Settings', [$this, 'settingsHeadingText'], 'drools_config');

    add_settings_field(
      'drools_url', // field name
      'Drools Endpoint (URL)', // label
      [$this, 'settingsFieldHtml'], // HTML content
      'drools_config', // page
      'drools_proxy', // section
      ['drools_url', '']
    );

    add_settings_field(
      'drools_user',
      'User',
      [$this, 'settingsFieldHtml'],
      'drools_config',
      'drools_proxy',
      ['drools_user', '']
    );

    add_settings_field(
      'drools_pass',
      'Password',
      [$this, 'settingsFieldHtml'],
      'drools_config',
      'drools_proxy',
      ['drools_pass', '']
    );

    register_setting('drools_settings', 'drools_url');
    register_setting('drools_settings', 'drools_user');
    register_setting('drools_settings', 'drools_pass');
  }

  /**
   * [settingsHeadingText description]
   */
  public function settingsHeadingText() {
    echo '<p>Enter your Drools credentials here.</p>';
  }

  /**
   * [settingsFieldHtml description]
   * @param   [type]  $args  [$args description]
   */
  public function settingsFieldHtml($args) {
    echo implode([
      '<input ',
      'type="text" ',
      'size="40" ',
      'name="' . $args[0] . '" ',
      'id="' . $args[0] . '" ',
      'value="' . get_option($args[0], '') . '" ',
      'placeholder="' . $args[1] . '" ',
      '/>'
    ], '');

    if (constant(strtoupper($args[0]))) {
      echo implode([
        '<p class="description">',
        'Environment currently set to ',
        '<code>' . constant(strtoupper($args[0])) . '</code>',
        '<p>'
      ], '');
    }
  }
}
