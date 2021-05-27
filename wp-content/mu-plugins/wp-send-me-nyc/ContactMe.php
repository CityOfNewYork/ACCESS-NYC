<?php

namespace SMNYC;

/**
 * Generic parent class for specific contact methods to extend
 * Creates AJAX hooks for you, and automatically includes CSRF protection
 */
class ContactMe {
  /** @property  String  Used in nonce hash and AJAX hook. */
  protected $action;

  /** @property  String  Used in settings/option verification. Must match the keyname used in settings eg. smnyc_SERVICE_user */
  protected $service;

  /** @property  String  Settings input label text. */
  protected $account_label;

  /** @property  String  Settings input label text. */
  protected $secret_label;

  /** @property  String  Settings input label text. */
  protected $from_label;

  /** @property  String  Settings input hint/placeholder text. */
  protected $account_hint;

  /** @property  String  Settings input hint/placeholder text. */
  protected $secret_hint;

  /** @property  String  Settings input hint/placeholder text. */
  protected $from_hint;

  /** @property  String  Pagename for add_settings_section() method. */
  protected $pagename = 'smnyc_config';

  /** @property  String  Fieldgroup name for register_setting() method. */
  protected $fieldgroup = 'smnyc_settings';

  /** @property  String  Prefix for field ids for add_settings_field() method. */
  protected $prefix = 'smnyc';

  /** @property  String  Text domain for custom post type label localization. */
  protected $text_domain = 'smnyc';

  /** @property  String  The template controller for the class. */
  protected $template_controller;

  /** @property  String  The post type for class template. */
  const POST_TYPE = 'smnyc';

  /**
   * Constructor
   */
  public function __construct($template_controller = false) {
    $this->createEndpoints();

    if ($template_controller) {
      $this->template_controller = $template_controller;
    }
  }

  /**
   * Set up AJAX hooks to each child's ::submission method.
   */
  protected function createEndpoints() {
    add_action('wp_ajax_' . strtolower($this->action) . '_send', [$this, 'submission']);
    add_action('wp_ajax_nopriv_' . strtolower($this->action) . '_send', [$this, 'submission']);
  }

  /**
   * Register post type for email content.
   */
  public function registerPostType() {
    register_post_type(self::POST_TYPE, array(
      'label' => __('SMNYC', 'text_domain'),
      'description' => __('Content for Send Me NYC', 'text_domain'),
      'labels' => array(
        'name' => _x('SMNYC', 'Post Type General Name', 'text_domain'),
        'singular_name' => _x('SMNYC', 'Post Type Singular Name', 'text_domain'),
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
   * Submission handler for the Share Form Component.
   */
  public function submission() {
    if (!isset($_POST['url']) || empty($_POST['url'])) {
      $this->failure(400, 'url required');
    }

    $valid = $this->validateNonce($_POST['hash'], $_POST['url']);
    $valid = $this->validConfiguration(strtolower($this->service));
    $valid = $this->validRecipient($_POST['to']);

    if ($valid) {
      $to = $this->sanitizeRecipient($_POST['to']);

      $guid = isset($_POST['GUID']) ? $_POST['GUID'] : '';

      $url = $_POST['url'];

      $share_text = isset($_POST['sharetext']) ? $_POST['sharetext'] : '';

      $url_shortened = $this->shorten($url);

      $template = $_POST['template'];

      $lang = (!isset($_POST['lang']) || empty($_POST['lang'])) ? 'en' : $_POST['lang'];

      $content = $this->content($url_shortened, $url, $share_text, $template, $lang);

      $this->send($to, $content);
      $this->success($content, $to, $guid, $url);
    }
  }

  /**
   * Creates a bit.ly shortened link to provided url. Fails silently.
   *
   * @param  String  $url  The URL to shorten.
   *
   * @return String        Shortened URL on success, original URL on failure.
   */
  private function shorten($url) {
    $bitly_shortener = get_option('smnyc_bitly_shortener');
    $bitly_token = get_option('smnyc_bitly_token');

    $bitly_shortener = (!empty($bitly_shortener)) ? $bitly_shortener : SMNYC_BITLY_SHORTENER;
    $bitly_token = (!empty($bitly_token)) ? $bitly_token : SMNYC_BITLY_TOKEN;

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
   * We verify it, hashed with the results being saved to make them page-unique.
   * Uses the wp_verify_nonce() method.
   *
   * @param   String   $nonce    The NONCE to validate
   * @param   String   $content  Postfix description of the nonce.
   *
   * @return  Boolean            Wether the nonce is valid or not.
   */

  protected function validateNonce($nonce, $content) {
    if (wp_verify_nonce($nonce, 'bsd_smnyc_token_' . $content) === false) {
      $this->failure(9, 'Invalid request');

      return false;
    } else {
      return true;
    }
  }

  /**
   * Just makes sure that the user, secret key, and from fields were filled out.
   *
   * @param   String   $service  Email or SMS.
   *
   * @return  Boolean            Wether the config is valid or not.
   */
  protected function validConfiguration($service) {
    $user = get_option('smnyc_' . $service . '_user');
    $from = get_option('smnyc_' . $service . '_from');

    $user = (!empty($user)) ?
      $user : constant('SMNYC_' . strtoupper($service) . '_USER');

    $from = (!empty($from)) ?
      $from : constant('SMNYC_' . strtoupper($service) . '_FROM');

    if (empty($user) || empty($from)) {
      $this->failure(-1, 'Invalid Configuration');

      return false;
    } else {
      return true;
    }
  }

  /**
   * Uses the wp_send_json() method to send a php key/value array as json response.
   *
   * @param   Array  $response  Key/value array of the response object.
   */
  protected function respond($response) {
    wp_send_json($response);
  }

  /**
   * Action hook for Stat Collector and sends success response key/value array.
   *
   * @param   String/Array  $content  Content sent in the email or sms.
   * @param   String        $to       Recipient of message.
   * @param   String        $guid     Session GUID.
   * @param   String        $url      URL to that has been shared.
   */
  protected function success($content = null, $to, $guid, $url) {
    /**
     * Action hook for Stat Collector to save message details to the DB
     *
     * @param   String  $type  Email/sms/whatever the class type is.
     * @param   String  $msg   The body of the message.
     */
    $type = $this->action;
    $msg = is_array($content) ? $content['body'] : $content;

    do_action('smnyc_message_sent', $type, $to, $guid, $url, $msg);

    /**
     * Send the success message
     */
    $this->respond(array(
      'success' => true,
      'error' => null,
      'message' => 'Sent!',
      'content' => $msg
    ));
  }

  /**
   * Sends a failer notice to the request.
   *
   * @param   Number   $code     The specific error code
   * @param   String   $message  The feedback message
   * @param   Boolean  $retry    Wether to retry
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
   * Bitly Settings Section.
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
      'section' => $section
    ));

    $this->registerSetting(array(
      'id' => $this->prefix . '_from',
      'title' => $this->from_label,
      'section' => $section
    ));
  }

  /**
   * Short hand function for adding and registering a setting. Used by child classes
   * to add additional settings for different sections.
   *
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
        'translate' => (isset($args['translate'])) ? $args['translate'] : false,
        'private' => (isset($args['private'])) ? $args['private'] : false
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
    echo '</p>';
  }

  /**
   * Callback function for add_settings_field(). Prints the input field and
   * environment setting if available. Registers the string for translation
   * via WPML.
   *
   * @param   object  $args  key > value object containing:
   *                         id = ID of the option
   *                         translate = Wether to register for translation
   */
  public function settingsFieldCallback($args) {
    $value = get_option($args['id'], '');

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

    /** Register this string for WPML translation */
    if ($args['translate']) {
      do_action('wpml_register_single_string', $this->text_domain, $args['id'], $value);

      echo '<p class="description">';
      echo __('This should be the default language. ');
      echo __('Create translations in WPML > String Translations. ');
      echo __('Look for the translation name ') . '<code>' . $args['id'] . '</code>';
      echo '</p>';
    }
  }

  /**
   * Applies the WPML single string translation filter to the desired option.
   *
   * @param   string  $id    The id of the option to pass to get_option() as
   *                         well as the registered name for the translated option.
   *
   * @return  string         The translated string. Defaults to english.
   */
  public function getTranslatedOption($id) {
    $string = apply_filters('wpml_translate_single_string', get_option($id, ''), $this->text_domain, $id);

    return $string;
  }
}
