<?php

namespace StatCollector;

class Settings {
  /** @var  Number  The default priority for this plugin's actions/hooks */
  public $priority = 10;

  /** @var  String  The prefix for all option IDs for this plugin */
  public $prefix = 'statc';

  /** @var  String  The title of the plugin */
  public $plugin = 'Stat Collector';

  /** @var  String  ID and slug of the settings page */
  public $page = 'collector_config';

  /** @var  String  The title of the settings page */
  public $admin_title = 'Stat Collector Settings';

  /** @var  String  The capibility required to be able to see the settings */
  public $capability = 'manage_options';

  /** @var  String  Title of the section */
  public $section_title = 'Database';

  /** @var  String  Section description */
  public $section_instructions = '<p>Enter connection information for the MySQL
    database. By default, Stat Collector will use existing environment variables
    for these settings but the values in these fields will override them.</p>';

  /** @var  String  Post fix for the option group */
  public $option_group = 'settings';

  /** @var  String  Postfix for host option ID */
  public $host = 'host';

  /** @var  String  Label for host option */
  public $host_label = 'Host (including port)';

  /** @var  String  Postfix for database option ID */
  public $database = 'database';

  /** @var  String  Label for database option */
  public $database_label = 'Name';

  /** @var  String  Postfix for database user option ID */
  public $user = 'user';

  /** @var  String  Label for database user option */
  public $user_label = 'Username';

  /** @var  String  Postfix for database password option ID */
  public $password = 'password';

  /** @var  String  Label for database password option */
  public $password_label = 'Password';

  /** @var  String  Postfix for bootstrapped flag option ID */
  public $bootstrapped = 'bootstrapped';

  /** @var  String  Label for bootstrapped flag option */
  public $bootstrapped_label = 'Tables Created';

  /** @var  String  Expected setting for bootstrapped flag option */
  public $bootstrapped_value = '5';

  /** @var  String  Checkbox label for bootstrapped flag option */
  public $bootstrapped_field = 'Checked if tables have been created. Tables are
    automatically created on the initial database query.';

  /** @var  Boolean  Disabled state for the bootstrapped flag option checkbox */
  private $bootstrapped_disabled = true;

  /** @var  String  Postfix for the ssl flag option ID */
  public $ssl = 'ssl';

  /** @var  String  Label for the ssl flag option */
  public $ssl_label = 'Certificate Authority';

  /** @var  String  Expected setting for the ssl flag option */
  public $ssl_value = '5';

  /** @var  String  Checkbox label for the ssl flag option */
  public $ssl_field = 'Checked if a certificate authority
    <code>ca.pem</code> is found in the file directory of this plugin. MySQL will
    be set to use a Secure Sockets Layer (SSL).';

  /** @var  String  Disabled state for the checkbox flag option */
  private $ssl_disabled = true;

  /** @var  String  Default filename for the ssl certificate authority */
  public $ssl_ca = 'ca.pem';

  /** @var  String  Postfix for the connection flag option ID */
  public $connection = 'connection';

  /** @var  String  Label for the connection flag option */
  public $connection_label = 'Connection';

  /** @var  String  Expected setting for the connection flag option */
  public $connection_value = '5';

  /** @var  String  Checkbox label for the connection flag option */
  public $connection_field = 'Checked if a connection can be made to the database
    using the credentials above. SSL is not required to make a connection.';

  /** @var  String  Disabled state for the checkbox flag option */
  private $connection_disabled = true;

  /** @var  String  Postfix for the section id for options */
  public $section_id = 'database';

  /** @var  String  Postfix for the section id for options */
  public $notify = 'notify';

  /** @var  String  Label for the notify flag option */
  public $notify_label = 'Send Notifications';

  /** @var  String  Expected setting for notify flag option */
  public $notify_value = '5';

  /** @var  String  Checkbox label for the notify flag option */
  public $notify_field = 'Check to nofity the admin if there is an
    error. This will be disabled on the first instance of an error, however, all
    errors are logged.';

  /** @var  String  Disabled state for the checkbox flag option */
  private $notify_disabled = false;

  /** @var  String  Amount of errors to tolerate before sending another error notification. */
  public $notify_error_count = 20;

  /**
   * Uses add_options_page to create page for the Stat Collector settings.
   * @link https://developer.wordpress.org/reference/functions/add_options_page/
   */
  public function addOptions() {
    add_options_page(
      $this->admin_title,
      $this->plugin,
      $this->capability,
      $this->page,
      function() {
        $statc_option_group = implode('_', [
          $this->prefix,
          $this->option_group
        ]);

        echo '<div class="wrap">';
        echo '  <h1>' . $this->admin_title . '</h1>';

        echo '  <form method="post" action="options.php">';
        do_settings_sections($this->page);

        settings_fields($statc_option_group);

        submit_button();
        echo '  </form>';
        echo '</div>';
      }
    );

    return $this;
  }

  /**
   * Create the settings sections within the WP Admin.
   */
  public function addSettings() {
    /**
     * Page Setting Keys
     */

    $statc_section_id = $this->prefix . '_' . $this->section_id;
    $statc_option_group = $this->prefix . '_' . $this->option_group;

    /**
     * Option Keys
     */

    $statc_host = $this->prefix . '_' . $this->host;
    $statc_database = $this->prefix . '_' . $this->database;
    $statc_user = $this->prefix . '_' . $this->user;
    $statc_password = $this->prefix . '_' . $this->password;
    $statc_bootstrapped = $this->prefix . '_' . $this->bootstrapped;
    $statc_ssl = $this->prefix . '_' . $this->ssl;
    $statc_connection = $this->prefix . '_' . $this->connection;
    $statc_notify = $this->prefix . '_' . $this->notify;

    /**
     * Add Settings Section
     */

    add_settings_section($statc_section_id, $this->section_title, function() {
      echo $this->section_instructions;
    }, $this->page);

    /**
     * Add Settings Fields
     */

    add_settings_field(
      $statc_host,                   // Field name
      $this->host_label,             // Label
      [$this, 'settingsFieldInput'], // HTML content
      $this->page,                   // Page
      $statc_section_id,             // Section ID
      array(                         // Args passed to callback
        'id' => $statc_host,
        'placeholder' => '',
        'private' => false
      )
    );

    add_settings_field(
      $statc_database,
      $this->database_label,
      [$this, 'settingsFieldInput'],
      $this->page,
      $statc_section_id,
      array(
        'id' => $statc_database,
        'placeholder' => '',
        'private' => false
      )
    );

    add_settings_field(
      $statc_user,
      $this->user_label,
      [$this, 'settingsFieldInput'],
      $this->page,
      $statc_section_id,
      array(
        'id' => $statc_user,
        'placeholder' => '',
        'private' => false
      )
    );

    add_settings_field(
      $statc_password,
      $this->password_label,
      [$this, 'settingsFieldInput'],
      $this->page,
      $statc_section_id,
      array(
        'id' => $statc_password,
        'placeholder' => '',
        'private' => true
      )
    );

    add_settings_field(
      $statc_bootstrapped,
      $this->bootstrapped_label,
      [$this, 'settingsFieldCheckbox'],
      $this->page,
      $statc_section_id,
      array(
        'id' => $statc_bootstrapped,
        'value' => $this->bootstrapped_value,
        'label' => $this->bootstrapped_field,
        'disabled' => $this->bootstrapped_disabled
      )
    );

    add_settings_field(
      $statc_ssl,
      $this->ssl_label,
      [$this, 'settingsFieldCheckbox'],
      $this->page,
      $statc_section_id,
      array(
        'id' => $statc_ssl,
        'value' => $this->ssl_value,
        'label' => $this->ssl_field,
        'disabled' => $this->ssl_disabled
      )
    );

    add_settings_field(
      $statc_connection,
      $this->connection_label,
      [$this, 'settingsFieldCheckbox'],
      $this->page,
      $statc_section_id,
      array(
        'id' => $statc_connection,
        'value' => $this->connection_value,
        'label' => $this->connection_field,
        'disabled' => $this->connection_disabled
      )
    );

    add_settings_field(
      $statc_notify,
      $this->notify_label,
      [$this, 'settingsFieldCheckbox'],
      $this->page,
      $statc_section_id,
      array(
        'id' => $statc_notify,
        'value' => $this->notify_value,
        'label' => $this->notify_field,
        'disabled' => $this->notify_disabled
      )
    );

    /**
     * Register settings with WordPress so they are saved
     */

    register_setting($statc_option_group, $statc_host);
    register_setting($statc_option_group, $statc_database);
    register_setting($statc_option_group, $statc_user);
    register_setting($statc_option_group, $statc_password);
    register_setting($statc_option_group, $statc_notify);

    /** If these are not disabled, they will need to be registered */
    // register_setting($statc_option_group, $statc_bootstrapped);
    // register_setting($statc_option_group, $statc_ssl);
    // register_setting($statc_option_group, $statc_connection);
  }

  /**
   * Callback for add_settings_field. Function that fills the field with the
   * desired form inputs. The function should echo its output.
   * @link https://developer.wordpress.org/reference/functions/add_settings_field/
   *
   * @param   Array  $args  Field arguments, [ID, Placeholder] of the text input
   */
  public function settingsFieldInput($args) {
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
}
