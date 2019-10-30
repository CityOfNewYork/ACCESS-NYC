<?php

namespace SMNYC;

/**
 * Generic parent class for specific contact methods to extend
 * Creates AJAX hooks for you, and automatically includes CSRF protection
 */
class ContactMe {
  /**
   * For child classes to override. Used in nonce hash and AJAX hook
   */
  protected $action;

  /**
   * For child classes to override. Used in settings/option verification.
   * Must match the keyname used in settings  e.g. smnyc_SERVICE_user
   */
  protected $service;

  /**
   * Settings page label hints, and placeholder text
   */
  protected $account_label;
  protected $secret_label;
  protected $from_label;

  protected $account_hint;
  protected $secret_hint;
  protected $from_hint;

  protected $pagename = 'smnyc_config';
  protected $fieldgroup = 'smnyc_settings';
  protected $prefix = 'smnyc';

  protected $text_domain = 'smnyc';

  const RESULTS_PAGE = 1;
  const OTHER_PAGE = 2;

  /**
   * Constructor
   */
  public function __construct() {
    $this->createEndpoints();
  }

  /**
   * [createEndpoints description]
   */
  protected function createEndpoints() {
    // Set up AJAX hooks to each child's ::submission method
    add_action('wp_ajax_' . strtolower($this->action) . '_send', [$this, 'submission']);
    add_action('wp_ajax_nopriv_' . strtolower($this->action) . '_send', [$this, 'submission']);
  }

  /**
   * Register post type for email content
   */
  public function registerPostType() {
    register_post_type('smnyc-email', array(
      'label' => __('SMNYC Email', 'text_domain'),
      'description' => __('Email content for Send Me NYC', 'text_domain'),
      'labels' => array(
        'name' => _x('SMNYC Emails', 'Post Type General Name', 'text_domain'),
        'singular_name' => _x('SMNYC Email', 'Post Type Singular Name', 'text_domain'),
      ),
      'hierarchical' => false,
      'public' => true,
      'show_ui' => true,
      'show_in_rest' => true,
      'has_archive' => false,
      'exclude_from_search' => true
    ));
  }

  /**
   * [submission description]
   */
  public function submission() {
    if (!isset($_POST['url']) || empty($_POST['url'])) {
      $this->failure(400, 'url required');
    }

    $this->validateNonce($_POST['hash'], $_POST['url']); // use nonce for CSRF protection
    $this->validConfiguration(strtolower($this->service)); // make sure credentials are specified
    $recipient = $this->validRecipient($_POST['to']); // also filters addressee

    $url = $this->shorten($_POST['url']); // SMS 160 char limit, should shorten URL

    // results pages have unique email content
    if ($this->isResultsUrl($_POST['url'])) {
      $content = $this->content($url, self::RESULTS_PAGE, $_POST['url']);
    } else {
      $content = $this->content($url, self::OTHER_PAGE, $_POST['url']);
    }

    $this->send($recipient, $content);
    $this->success($content);
  }

  /**
   * Creates a bit.ly shortened link to provided url. Fails silently
   * @param  $url    string     The URL to shorten
   * @return string  shortened  URL on success, original URL on failure
   */
  private function shorten($url) {
    $bitly_shortener = get_option('smnyc_bitly_shortener');
    $bitly_token = get_option('smnyc_bitly_token');

    $bitly_shortener = (!empty($bitly_shortener)) ? $bitly_shortener : $_ENV['SMNYC_BITLY_SHORTENER'];
    $bitly_token = (!empty($bitly_token)) ? $bitly_token : $_ENV['SMNYC_BITLY_TOKEN'];

    $encoded = urlencode($url);

    $request = $bitly_shortener . '?access_token=' . $bitly_token . '&longUrl=' . $encoded;

    $bitly = wp_remote_get($request);

    if (is_wp_error($bitly)) {
      return $url;
    }

    $j = json_decode(wp_remote_retrieve_body($bitly));

    if ($j->status_code !== 200) {
      return $url;
    }

    return $j->data->url;
  }

  /**
   * To prevent CSRF attacks, and to otherwise protect an open SMS/Email relay.
   * AJAX call should be given a nonce by the webpage, and must submit it back.
   * We verify it, hashed with the results being saved to make them page-unique
   * @param   [type]  $nonce    [$nonce description]
   * @param   [type]  $content  [$content description]
   */
  protected function validateNonce($nonce, $content) {
    if (wp_verify_nonce($nonce, 'bsd_smnyc_token_' . $content) === false) {
      $this->failure(9, 'Invalid request');
    }
  }

  /**
   * Just makes sure that the user, secret key, and from fields were filled out
   * @param   [type]  $service  [$service description]
   */
  protected function validConfiguration($service) {
    $user = get_option('smnyc_' . $service . '_user');
    $secret = get_option('smnyc_' . $service . '_secret');
    $from = get_option('smnyc_' . $service . '_from');

    $user = (!empty($user)) ? $user : $_ENV['SMNYC_' . strtoupper($service) . '_USER'];
    $secret = (!empty($secret)) ? $secret : $_ENV['SMNYC_' . strtoupper($service) . '_SECRET'];
    $from = (!empty($from)) ? $from : $_ENV['SMNYC_' . strtoupper($service) . '_FROM'];

    if (empty($user) || empty($secret) || empty($from)) {
      $this->failure(-1, 'Invalid Configuration');
    }
  }

  /**
   * [isResultsUrl description]
   * @param   [type]  $url  [$url description]
   * @return  [type]        [return description]
   */
  protected function isResultsUrl($url) {
    $path = parse_url($_POST['url'], PHP_URL_PATH);

    return preg_match('/.*\/eligibility\/results\/?$/', $path);
  }

  /**
   * Helper functions for JSON responses
   * @param   [type]  $response  [$response description]
   */
  protected function respond($response) {
    wp_send_json($response);

    wp_die();
  }

  /**
   * [success description]
   * @param   [type]  $content  [$content description]
   */
  protected function success($content = null) {
    /**
     * Action hook for Stat Collector to save message details to the DB
     * @param   [type]  $type  email/sms/whatever the class type is
     * @param   [type]  $to    The number/email sent to
     * @param   [type]  $uid   The GUID of the results
     * @param   [type]  $url   The main url shared
     * @param   [type]  $msg   The body of the message
     */
    $type = $this->action;
    $to = $_POST['to'];
    $uid = isset($_POST['GUID']) ? $_POST['GUID'] : '0';
    $url = $_POST['url'];
    $msg = is_array($content) ? $content['body'] : $content;

    do_action('results_sent', $type, $to, $uid, $url, $msg);

    /**
     * Send the success message
     */
    $this->respond(array(
      'success' => true,
      'error' => null,
      'message' => null
    ));
  }

  /**
   * [failure description]
   * @param   [type]  $code     [$code description]
   * @param   [type]  $message  [$message description]
   * @param   [type]  $retry    [$retry description]
   */
  protected function failure($code, $message, $retry = false) {
    $this->respond([
      'success' => false,
      'error' => $code,
      'message' => $message,
      'retry' => $retry
    ]);
  }

  /**
   * Bitly Settings Section
   */
  public function createBitlySection() {
    $section = $this->prefix . '_bitly_section';

    add_settings_section(
      $section,
      'Bitly Settings',
      [$this, 'bitlyHeadingText'],
      $this->pagename
    );

    $this->registerSetting(array(
      'id' => $this->prefix . '_bitly_shortener',
      'title' => 'Bitly Shortening API Link',
      'section' => $section
    ));

    $this->registerSetting(array(
      'id' => $this->prefix . '_bitly_token',
      'title' => 'Bitly Access Token',
      'section' => $section
    ));
  }

  /**
   * Register settings for the form. Child classes can inherit this function
   * and create additional settings for their classes.
   */
  public function createSettingsSection() {
    $section = $this->prefix . '_section';

    add_settings_section(
      $section,
      $this->action . ' Settings',
      [$this, 'settingsHeadingText'],
      $this->pagename
    );

    $this->registerSetting(array(
      'id' => $this->prefix . '_user',
      'title' => $this->account_label,
      'section' => $section,
    ));

    $this->registerSetting(array(
      'id' => $this->prefix . '_secret',
      'title' => $this->secret_label,
      'section' => $section,
    ));

    $this->registerSetting(array(
      'id' => $this->prefix . '_from',
      'title' => $this->from_label,
      'section' => $section,
    ));
  }

  /**
   * Short hand function for adding and registering a setting. Used by child classes
   * to add additional settings for different sections.
   * @param   object  $args  key > value object containing:
   *                         id = ID of the option
   *                         title = Label for the option
   *                         section = Section the option appears in
   *                         translate = Wether to register for translation
   */
  public function registerSetting($args) {
    add_settings_field(
      $args['id'],
      $args['title'],
      [$this, 'settingsFieldCallback'],
      $this->pagename,
      $args['section'],
      array(
        'id' => $args['id'],
        'translate' => (isset($args['translate'])) ? $args['translate'] : false
      )
    );

    register_setting($this->fieldgroup, $args['id']);
  }

  /**
   * Callback function for the section heading text used by add_settings_section().
   */
  public function bitlyHeadingText() {
    echo '<p>';
    echo '  Enter your Bitly settings here. ';
    echo '</p>';
  }

  /**
   * Callback function for the section heading text used by add_settings_section().
   */
  public function settingsHeadingText() {
    echo '<p>';
    echo '  Enter your ' . $this->service . ' credentials here. ';
    echo '  Values with <b>WPML</b> can be managed in <b>WPML</b> > <b>String Translations</b>.';
    echo '  The text domain for this plugin is <b>smnyc</b>.';
    echo '</p>';
  }

  /**
   * Callback function for add_settings_field(). Prints the input field and
   * environment setting if available. Registers the string for translation
   * via WPML.
   * @param   object  $args  key > value object containing:
   *                         id = ID of the option
   *                         translate = Wether to register for translation
   */
  public function settingsFieldCallback($args) {
    $id = $args['id'];
    $value = get_option($id, '');

    echo "<input ";
    echo "type=\"text\" ";
    echo "name=\"$id\" ";
    echo "size=40 ";
    echo "id=\"$id\" ";
    echo "value=\"$value\" ";
    echo "/>";

    /** Display environment variable if available */
    if ($_ENV[strtoupper($args['id'])]) {
      echo '<p class="description">';
      echo '  Environment currently set to <code>' . $_ENV[strtoupper($id)] . '</code>';
      echo '<p>';
    }

    /** Register this string for WPML translation */
    if ($args['translate']) {
      do_action('wpml_register_single_string', $this->text_domain, $id, $value);

      echo '<p class="description">';
      echo '  Translation name <code>' . $id . '</code>';
      echo '</p>';
    }
  }

  /**
   * Applies the WPML single string translation filter to the desired option
   * @param   string  $id    The id of the option to pass to get_option() as
   *                         well as the registered name for the translated option.
   * @return  string         The translated string. Defaults to english.
   */
  public function getTranslatedOption($id) {
    $string = apply_filters('wpml_translate_single_string', get_option($id, ''), $this->text_domain, $id);
    return $string;
  }
}
