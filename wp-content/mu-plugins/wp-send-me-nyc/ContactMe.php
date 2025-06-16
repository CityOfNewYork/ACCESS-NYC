<?php

namespace SMNYC;

use Exception;
use Timber;

/**
 * Generic parent class for specific contact methods to extend
 * Creates AJAX hooks for you, and automatically includes CSRF protection
 */
class ContactMe {
  /** @property  String  Used in nonce hash and AJAX hook. */
  public $action;

  /** @property  String  Used in Admin Settings. */
  public $action_label;

  /** @property  String  Used in settings/option verification. Must match the keyname used in settings eg. smnyc_SERVICE_user */
  public $service;

  /** @property  String  The service type. Used in admin setting strings */
  public $type;

  /** @property  String  Settings input label text. */
  public $account_label;

  /** @property  String  Settings input label text. */
  public $secret_label;

  /** @property  String  Settings input label text. */
  public $from_label;

  /** @property  String  Pagename for add_settings_section() method. */
  public $pagename = 'smnyc_config';

  /** @property  String  Fieldgroup name for register_setting() method. */
  public $fieldgroup = 'smnyc_settings';

  /** @property  String  Prefix for field ids for add_settings_field() method. */
  public $prefix = 'smnyc';

  /** @property  String  Text domain for custom post type label localization. */
  public $text_domain = 'smnyc';

  /** @property  String  The template controller for the class. */
  public $template_controller;

  /** @property  String  The post type for the template content. */
  public $post_type = 'smnyc';

  /** @property  String  The menu label for posts. */
  public $post_type_label = 'SMNYC';

  /** @property  String  The post type description. */
  public $post_type_description = 'SMNYC';

  /** @property  String  The menu label name for posts. */
  public $post_type_name = 'SMNYC';

  /** @property  String  The singular menu label name for posts. */
  public $post_type_name_singular = 'SMNYC';

  /**
   * Constructor
   */
  public function __construct($template_controller = false) {
    if ($template_controller) {
      $this->template_controller = $template_controller;
    }
  }

  /**
   * Return option prefixes, ajax action names, and post type names for
   * maintaining consistency across services
   *
   * @return  Array  An array of different option strings
   */
  public function info() {
    $ajax = strtolower($this->action) . '_send';
    $prefix = strtolower($this->prefix . '_' . $this->service . '_');

    return array(
      'actions' => array(
        'private' => 'wp_ajax_' . $ajax,
        'anonymous' => 'wp_ajax_nopriv_' . $ajax
      ),
      'post_type' => $this->post_type,
      'settings_section' => $this->prefix . '_section',
      'option_prefix' => $prefix,
      'constant_prefix' => strtoupper($prefix)
    );
  }

  /**
   * Set up AJAX hooks to each child's ::submission method
   *
   * @return  Object  Instance of $this
   */
  public function createEndpoints() {
    add_action($this->info()['actions']['private'], [$this, 'submission']);
    add_action($this->info()['actions']['anonymous'], [$this, 'submission']);

    return $this;
  }

  /**
   * Register post type for template content
   *
   * @return  Object  Instance of $this
   */
  public function registerPostType() {
    register_post_type($this->info()['post_type'], array(
      'label' => __($this->post_type_label, $this->text_domain),
      'description' => __($this->post_type_description, $this->text_domain),
      'labels' => array(
        'name' => __($this->post_type_name, $this->text_domain),
        'singular_name' => __($this->post_type_name_singular, $this->text_domain),
        'all_items' => __('All ' . $this->service . ' Templates', $this->text_domain),
      ),
      'hierarchical' => false,
      'public' => true,
      'show_ui' => true,
      'show_in_rest' => true,
      'has_archive' => false,
      'exclude_from_search' => true
    ));

    return $this;
  }

  /**
   * Submission handler for the Share Form Component.
   */
  public function submission() {
    if (!isset($_POST['url']) || empty($_POST['url'])) {
      $this->failure(400, 'url required');
    }

    // URL must belong to current site
    if (!str_starts_with($_POST['url'], get_site_url(null, '', 'https')) && !str_starts_with($_POST['url'], get_site_url(null, '', 'http'))) {
      $this->failure(400, 'invalid URL');
    }

    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $visitor_id = $_COOKIE['visitor_id'];

    $valid = $this->validateNonce($_POST['hash'], $_POST['url']);
    $valid = $valid && $this->validConfiguration();
    $valid = $valid && $this->validRecipient($_POST['to']);
    // $valid = $valid && !$this->is_rate_limited($_POST['to'] . $_POST['url'], 'to_address_url', 1, DAY_IN_SECONDS);

    if ($valid) {
      $to = $this->sanitizeRecipient($_POST['to']);

      $guid = isset($_POST['GUID']) ? $_POST['GUID'] : '';

      $url = sanitize_url($_POST['url']);

      $url_shortened = (is_plugin_active('wp-bitly/wp-bitly.php')) ? $this->shorten($url) : $url;

      $program_name = '';

      if(isset($_POST['post_id'])) {
        $post = Timber::get_post($_POST['post_id']);
        if ($post && isset($post->custom['program_name'])) {
          $program_name = $post->custom['program_name'];
        }
      }

      $template = $_POST['template'];

      $lang = (!isset($_POST['lang']) || empty($_POST['lang'])) ? 'en' : $_POST['lang'];

      $content = $this->content($url_shortened, $url, $program_name, $template, $lang);

      $this->send($to, $content);
      $this->success($to, $guid, $url, $ip_address, $user_agent, $visitor_id, $content);
    }
  }

  /**
   * Creates a bit.ly shortened link to provided url using settings from the
   * WordPress Bit.ly plugin. Fails silently and returns the original URL.
   * The Bit.ly API will only work for URLS with production level domains.
   *
   * @param  String  $url  The URL to shorten.
   *
   * @return String        Shortened URL on success, original URL on failure.
   */
  private function shorten($url) {
    try {
      /**
       * TODO: Add method to retrieve existing shortlink from post meta storage (if it is a post).
       */

      // Get WP Bit.ly Settings
      $wpBitlyOptions = wp_parse_args(get_option(WPBITLY_OPTIONS, array(
        'oauth_token' => '',
        'default_domain' => '',
        'default_group' => ''
      )));

      $token = $wpBitlyOptions['oauth_token'];
      $domain = $wpBitlyOptions['default_domain'];
      $group = $wpBitlyOptions['default_group'];

      $options = array('long_url' => $url);

      if ($domain) {
        $options['domain'] = $domain;
      }

      if ($group) {
        $options['group_guid'] = $group;
      }

      $response = wp_remote_post(WPBITLY_BITLY_API . 'shorten', array(
        'timeout' => '30',
        'headers' => array(
          'Authorization' => 'Bearer ' . $token,
          'Content-Type' => 'application/json'
        ),
        'method'  => 'POST',
        'body' => json_encode($options)
      ));

      if (200 === $response['response']['code'] || 201 === $response['response']['code']) {
        $body = json_decode($response['body'], true);

        /**
         * TODO: Add method to store shortlink in post meta storage (if it is a post).
         */

        return $body['link'];
      } else {
        if ( is_wp_error( $response ) ) {
          $error_message = $response->get_error_message();
          throw new Exception($error_message);
        }
        else {
          throw new Exception($response['response']['code'] . ' ' . $response['body']);
        }
      }
    } catch (Exception $e) {
      $msg = sprintf('Send Me NYC: Bit.ly URL shortening skipped for %s: %s', $url, $e->getMessage());

      // WP debug.log
      error_log($msg);

      // Send log to Query Monitor
      do_action('qm/debug', $msg);

      return $url;
    }
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
   * @return  Boolean            Wether the config is valid or not.
   */
  protected function validConfiguration() {
    $user = get_option($this->info()['option_prefix'] . 'user');
    $from = get_option($this->info()['option_prefix'] . 'from');

    $user = (!empty($user))
      ? $user : constant($this->info()['constant_prefix'] . 'USER');

    $from = (!empty($from))
      ? $from : constant($this->info()['constant_prefix'] . 'FROM');

    if (empty($user) || empty($from)) {
      $this->failure(-1, 'Invalid Configuration');

      return false;
    } else {
      return true;
    }
  }

  /**
   * Determines whether the action should be rate limited
   * 
   * @param   String   $value     The value that we are checking
   * @param   String   $type      The type of value (to_address or ip_address)
   * @param   Number   $limit     The maximum number of events that can occur in the interval before rate limiting starts
   * @param   Number   $interval  The number of seconds in the interval
   * 
   * @return true if the action should be rate limited, false otherwise
   */
  private function is_rate_limited($value, $type, $limit = 15, $interval = 3600) {
    $key = 'rate_limit_' . $type . '_' . md5($value);
    $attempts = get_transient($key);

    if (!$attempts) {
        set_transient($key, 1, $interval);
        return false;
    }

    if ($attempts < $limit) {
        set_transient($key, $attempts + 1, $interval);
        return false;
    }

    return true;
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
   * @param   String        $to           Recipient of message.
   * @param   String        $guid         Session GUID.
   * @param   String        $url          URL to that has been shared.
   * @param   String        $ip_address   IP address of the sender
   * @param   String        $user_agent   user-agent header of the sender
   * @param   String        $visitor_id   visitor_id cookie of the sender
   * @param   String/Array  $content      Content sent in the email or sms.
   */
  protected function success($to, $guid, $url, $ip_address, $user_agent, $visitor_id, $content = null) {
    /**
     * Action hook for Stat Collector to save message details to the DB
     *
     * @param   String  $type  Email/sms/whatever the class type is.
     * @param   String  $msg   The body of the message.
     */
    $type = $this->action;
    $msg = is_array($content) ? $content['body'] : $content;

    do_action('smnyc_message_sent', $type, $to, $guid, $url, $msg, $ip_address, $user_agent, $visitor_id);

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
   * Register settings for the form. Child classes can inherit this function
   * and create additional settings for their classes.
   */
  public function createSettingsSection() {
    add_settings_section(
      $this->info()['settings_section'],
      $this->action_label . ' Settings',
      [$this, 'settingsHeadingText'],
      $this->pagename
    );

    $this->registerSetting(array(
      'id' => $this->info()['option_prefix'] . 'user',
      'title' => $this->account_label,
      'section' => $this->info()['settings_section']
    ));

    $this->registerSetting(array(
      'id' => $this->info()['option_prefix'] . 'from',
      'title' => $this->from_label,
      'section' => $this->info()['settings_section']
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
    echo '  Enter your ' . $this->service . ' ' . $this->type . ' credentials here. ';
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
