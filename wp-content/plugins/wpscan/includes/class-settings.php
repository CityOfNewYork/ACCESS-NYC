<?php

class WPScan_Settings extends WPScan {

  // Page slug
  static private $page;

  /*
  * Initialize
  */
  static public function init() {

    self::$page = 'wpscan_settings';

    add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
    add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
    add_action( 'admin_notices', array( __CLASS__, 'got_api_token' ) );

    add_action( 'add_option_' . self::OPT_API_TOKEN, array( __CLASS__, 'can_check_now' ) , 10, 2 );
    add_action( 'update_option_' . self::OPT_API_TOKEN, array( __CLASS__, 'can_check_now' ) , 10, 3 );

    add_action( 'add_option_' . self::OPT_SCANNING_INTERVAL, array( __CLASS__, 'schedule_event' ) , 10, 2 );
    add_action( 'update_option_' . self::OPT_SCANNING_INTERVAL, array( __CLASS__, 'schedule_event' ) , 10, 3 );
    add_action( 'update_option_' . self::OPT_SCANNING_TIME, array( __CLASS__, 'schedule_event' ) , 10, 3 );

  }

  /*
  * Settings Options
  */
  static public function admin_init() {

    register_setting( self::$page, self::OPT_API_TOKEN, array( __CLASS__, 'sanitize_api_token') );
    register_setting( self::$page, self::OPT_SCANNING_INTERVAL, 'sanitize_text_field' );
    register_setting( self::$page, self::OPT_SCANNING_TIME, 'sanitize_text_field' );
    register_setting( self::$page, self::OPT_IGNORE_ITEMS );

    $section = self::$page . '_section';

    add_settings_section(
      $section,
      null,
      array( __CLASS__, 'introduction' ),
      self::$page
    );

    add_settings_field(
      self::OPT_API_TOKEN,
      __( 'WPScan API Token', 'wpscan' ),
      array( __CLASS__, 'field_api_token' ),
      self::$page,
      $section
    );

    add_settings_field(
      self::OPT_SCANNING_INTERVAL,
      __( 'Automated Scanning', 'wpscan' ),
      array( __CLASS__, 'field_scanning_interval' ),
      self::$page,
      $section
    );

    add_settings_field(
      self::OPT_SCANNING_TIME,
      __( 'Scanning Time', 'wpscan' ),
      array( __CLASS__, 'field_scanning_time' ),
      self::$page,
      $section
    );

    add_settings_field(
      self::OPT_IGNORE_ITEMS,
      __( 'Ignore Items', 'wpscan' ),
      array( __CLASS__, 'field_ignore_items' ),
      self::$page,
      $section
    );

    if ( self::is_interval_scanning_disabled() ) {
      wp_clear_scheduled_hook( self::WPSCAN_SCHEDULE );
    }
  }

  /*
  * Check if API Token is set
  */
  static public function api_token_set() {

    $api_token = get_option( self::OPT_API_TOKEN );
    if ( empty( $api_token ) ) {
      return false;
    }
    return true;

  }

  /*
  * Warn if no API Token is set
  */
  static public function got_api_token() {

    $screen = get_current_screen();

    if ( ! self::api_token_set() && ! strstr( $screen->id, self::$page ) ) {

      printf(
        '<div class="%s"><p>%s <a href="%s">%s</a>%s</p></div>',
        'notice notice-error',
        __( 'To use WPScan you have to setup your WPScan API Token. Either in the ', 'wpscan' ),
        admin_url( 'admin.php?page=' . self::$page ),
        __( 'Settings', 'wpscan' ),
        __( ' page, or, within the wp-config.php file.', 'wpscan' )
      );

    }

  }

  /*
  * Add Submenu
  */
  static public function menu() {

    add_submenu_page(
      'wpscan',
      __( 'Settings', 'wpscan' ),
      __( 'Settings', 'wpscan' ),
      self::WPSCAN_ROLE,
      self::$page,
      array( __CLASS__, 'page' )
    );

  }

  /*
  * Page
  */
  static public function page() {

    echo '<div class="wrap">';
    echo '<h1><img src="' . self::$plugin_url . 'assets/svg/logo.svg" alt="WPScan"></h1>';
    echo '<h2>' . __( 'Settings', 'wpscan' ) . '</h2>';
    echo '<p>' . __( 'The WPScan WordPress security plugin uses our own constantly updated vulnerability database to stay up to date with the latest WordPress core, plugin and theme vulnerabilities. For the WPScan plugin to retrieve the potential vulnerabilities that may affect your site, you first need to configure your API token, that you can get for free from our database\'s website. Alternatively you can also set your API token in the wp-config.php file using the WPSCAN_API_TOKEN constant.', 'wpscan' ) . '</p>';
    settings_errors();
    echo '<form action="options.php" method="post">';
    settings_fields( self::$page );
    do_settings_sections( self::$page );

    submit_button();

    echo '</form>';
    echo '</div>';

  }

  /*
  * Introduction
  */
  static public function introduction() { }

  /*
  * Field API Token
  */
  static public function field_api_token() {
    $api_token = esc_attr( get_option( self::OPT_API_TOKEN ) );

    if ( defined('WPSCAN_API_TOKEN') ) {
      $api_token = esc_attr(WPSCAN_API_TOKEN);
      $disabled = "disabled='true'";
    } else {
      $disabled = null;
    }

    // Field
    echo  "<input type='text' name='".self::OPT_API_TOKEN."' value='$api_token' class='regular-text' $disabled>";

    // Messages
    echo '<p class="description">';
    
    if ( defined('WPSCAN_API_TOKEN') ) {
      _e("Your API Token has been set in a PHP file and been disabled here.", 'wpscan');
      echo "<br>";
    } 

    if (! empty($api_token)) {
      echo sprintf(
        __( 'To regenerate your token, or upgrade your plan, %s.', 'wpscan' ),
        '<a href="' . WPSCAN_PROFILE_URL . '" target="_blank">' . __( 'check your profile', 'wpscan' ) . '</a>'
        );
    } else {
      echo sprintf(
        __( '%s to get your free API Token.', 'wpscan' ),
        '<a href="' . WPSCAN_SIGN_UP_URL . '" target="_blank">' . __( 'Sign up', 'wpscan' ) . '</a>'
        );
    }

    echo '</p><br>';

  }

  /*
  * Field scanning interval
  */
  static public function field_scanning_interval() {
    
    $opt_name = self::OPT_SCANNING_INTERVAL;
    $value = esc_attr( get_option( $opt_name , 'daily' ) );
    
    $disabled = self::is_interval_scanning_disabled() ? "disabled='true'" : null;
    
    $options = array(
      'daily' => __('Daily', 'wpscan'),
      'twicedaily' => __('Twice daily', 'wpscan'),
      'hourly' => __('Hourly', 'wpscan'),
    );

    echo "<select name='$opt_name' $disabled>";
      foreach ($options as $id => $title) {
        $selected = selected($value, $id, false);
        echo "<option value='$id' $selected>$title</option>";
      }
    echo "</select>";

    echo "<br/>";

    echo '<p class="description">';

    if ( self::is_interval_scanning_disabled() ) {
      _e('Automated scanning is currently disabled using the <code>WPSCAN_DISABLE_SCANNING_INTERVAL</code> constant.', 'wpscan');
    } else {
      _e("This setting will change the frequency that the WPScan plugin will run an automatic scan. This is useful if you want your report, or notifications, to be updated more frequently. Please note that the more frequent scans are run, the more API requests are consumed.", 'wpscan');
    }

    echo "</p><br>";

  }


  /*
  * Field scanning time
  */
  static public function field_scanning_time() {

    $opt = self::OPT_SCANNING_TIME;
    $value = esc_attr( get_option( $opt , '12:00' ) );
    $disabled = self::is_interval_scanning_disabled() ? "disabled='true'" : null;

    echo  "<input type='time' name='$opt' value='$value' $disabled> ";
    
    if ( !self::is_interval_scanning_disabled() ) {
      echo __('Current Server time is ', 'wpscan') . '<code>' . date("H:i") . '</code>';
    }


    echo "<br/><br/>";

    echo '<p class="description">';

    if ( self::is_interval_scanning_disabled() ) {
      _e('Automated scanning is currently disabled using the <code>WPSCAN_DISABLE_SCANNING_INTERVAL</code> constant.', 'wpscan');
    } else {
      _e('This setting allows you to set the scanning hour for the <code>Daily</code> option. For the <code>Twice Daily</code> this will be the first scan and the second will be 12 hours later. For the <code>Hourly</code> it will affect the first scan only.' , 'wpscan');
    }

    echo "</p><br/>";

  }

  /*
  * Field ignore items
  */
  static public function field_ignore_items() {
    
    $opt = self::OPT_IGNORE_ITEMS;
    $value = get_option($opt, []);
    
    $wp = isset($value['wordpress']) ? 'checked ': null;

    $section_css = '
      style="display: block;
      margin-bottom: 25px;
      float: left;
      width: 800px;"';

    $item_css = '
      style="width: 30%;
      float: left;
      margin-bottom: 12px;
      padding-right: 20px;
      line-break: anywhere;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      box-sizing: border-box;"';
    
    /**
     * WordPress
     */
    echo "<div $section_css>";
      echo "<label><input name='{$opt}[wordpress]' type='checkbox' $wp value='1' > " . __('WordPress Core', 'wpscan') . "</label>";
    echo '</div>';

    /**
     * Plugins list
     */
    echo "<div $section_css>";
    
      echo '<h4>'. __('Plugins', 'wpscan') .'</h4>';

      foreach ( get_plugins() as $name => $details ) {
        $slug = self::get_plugin_slug( $name, $details );
        $checked = isset($value['plugins'][$slug]) ? 'checked ': null;

        echo "<label $item_css><input name='{$opt}[plugins][$slug]' type='checkbox' $checked value='1'> ". esc_html($details['Name']) . "</label>";
      }

    echo '</div>';

    /**
     * Themes list
     */
    echo "<div $section_css>";
    
      echo '<h4>'. __('Themes', 'wpscan') .'</h4>';

      foreach ( wp_get_themes() as $name => $details ) {
        $slug = self::get_theme_slug( $name, $details );
        $checked = isset($value['themes'][$slug]) ? 'checked ': null;

        echo "<label $item_css><input name='{$opt}[themes][$slug]' type='checkbox' $checked value='1'> " . esc_html($details['Name']) . "</label>";
      }

    echo '</div>';
    
  }

  /*
  * Sanitize API Token
  */
  static public function sanitize_api_token( $value ) {

    $value = trim($value);
    $result = self::api_get( '/status', $value );

    if( $result ===  401 || $result ===  403 ) {

      add_settings_error(
        self::$page,
        'api_token',
        __( 'You have entered an invalid API Token.', 'wpscan' )
      );

      return false;

    } else {
      if ( self::is_interval_scanning_disabled() ) {
        wp_clear_scheduled_hook( self::WPSCAN_SCHEDULE );
      }
    }

    return $value;

  }

  /*
  * Schedule scanning event
  */
  static public function schedule_event($old_value, $value) {

    $api_token = get_option( self::OPT_API_TOKEN );

    if ( ! empty( $api_token ) && $old_value !== $value) {

      $interval = esc_attr( get_option( self::OPT_SCANNING_INTERVAL , 'daily' ) );
      $time = esc_attr( get_option( self::OPT_SCANNING_TIME , '12:00' ) );

      // enable cron job if it's a valid API Token
      wp_clear_scheduled_hook( self::WPSCAN_SCHEDULE ); // Prevent duplication

      if ( !self::is_interval_scanning_disabled() ) {
        wp_schedule_event( strtotime($time), $interval, self::WPSCAN_SCHEDULE );
      }

    }
    
  }

  /*
  * Check if there is an API Token to check now for vulnerabilities
  */
  static public function can_check_now($old_value, $value) {
    if ( ! empty( $value ) && $old_value !== $value) {
      self::check_now();
    }
  }

}
