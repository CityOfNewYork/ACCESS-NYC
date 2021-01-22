<?php

class WPScan {

  // Constants
  // Settings
  const OPT_API_TOKEN = 'wpscan_api_token';
  const OPT_SCANNING_INTERVAL = 'wpscan_scanning_interval';
  const OPT_SCANNING_TIME = 'wpscan_scanning_time';
  const OPT_IGNORE_ITEMS = 'wpscan_ignore_items';

  // Account
  const OPT_ACCOUNT_STATUS = 'wpscan_account_status';

  // Notifications
  const OPT_EMAIL     = 'wpscan_mail';
  const OPT_INTERVAL  = 'wpscan_interval';
  const OPT_IGNORED   = 'wpscan_ignored';

  // Report
  const OPT_REPORT = 'wpscan_report';

  // Schedule
  const WPSCAN_SCHEDULE = 'wpscan_schedule';

  // Dashboard
  const WPSCAN_DASHBOARD = 'wpscan_dashboard';

  // Script
  const WPSCAN_SCRIPT = 'wpscan_script';

  // Transient
  const WPSCAN_TRANSIENT_CRON = 'wpscan_doing_cron';

  // Actions
  const WPSCAN_ACTION_CHECK = 'wpscan_check_now';

  // required minimal role
  const WPSCAN_ROLE = 'manage_options';

  // Plugin path
  static public $plugin_dir = '';

  // Plugin URI
  static public $plugin_url = '';

  // Page
  static public $page_hook = 'toplevel_page_wpscan';

  // Report shortcut
  static public $report = array();

  /*
  * Initialize actions
  */
  static public function init() {

    self::$plugin_dir = plugin_dir_path( WPSCAN_PLUGIN_FILE );
    self::$plugin_url = plugin_dir_url( WPSCAN_PLUGIN_FILE );

    // Languages
    load_plugin_textdomain( 'wpscan', false, self::$plugin_dir . 'languages' );
    
    // Report
    self::$report = get_option( self::OPT_REPORT );

    // Hooks
    add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
    add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue' ) );
    add_filter( 'plugin_action_links_' . plugin_basename( WPSCAN_PLUGIN_FILE ), array( __CLASS__, 'add_action_links' ) );

    if ( defined('WPSCAN_API_TOKEN') ) {
      add_action( 'admin_init', array( __CLASS__, 'api_token_from_constant' ) );
    }
    
    // Micro apps
    WPScan_Report::init();
    WPScan_Settings::init();
    WPScan_Account::init();
    WPScan_Summary::init();
    WPScan_Notification::init();
    WPScan_Admin_Bar::init();
    WPScan_Dashboard::init();
    WPScan_Sitehealth_integration::init();

  }

  /*
  * Plugins Loaded
  */
  static public function loaded() {

    // Languages
    load_plugin_textdomain( 'wpscan', false, self::$plugin_dir . 'languages' );

  }

  /*
  * Activate actions
  */
  static public function activate() { }

  /*
  * Deactivate actions
  */
  static public function deactivate() {

    wp_clear_scheduled_hook( self::WPSCAN_SCHEDULE );

  }

  /**
   * Use the global constant WPSCAN_API_TOKEN if defined.
   * 
   * @example define('WPSCAN_API_TOKEN', 'xxx');
   * 
   */
  static public function api_token_from_constant() {

    if ( get_option( self::OPT_API_TOKEN ) !== WPSCAN_API_TOKEN ) {
        $sanitize = WPScan_Settings::sanitize_api_token(WPSCAN_API_TOKEN);
      if ( $sanitize ) {
        update_option( self::OPT_API_TOKEN, WPSCAN_API_TOKEN );
      } else {
        delete_option(self::OPT_API_TOKEN);
      }
    }

  }

  /*
  *  Register Admin Scripts
  */
  static public function admin_enqueue( $hook ) {

    $screen = get_current_screen();

    // enqueue only on wpscan pages and on dashboard (widgets)
    if ( $hook === self::$page_hook || $screen->id === 'dashboard' ) {
      wp_enqueue_style( 'wpscan', plugins_url( 'assets/css/style.css', WPSCAN_PLUGIN_FILE ) );
    }

    // only enqueue in wpscan pages
    if ( $hook === self::$page_hook ) {
      wp_enqueue_script( self::WPSCAN_SCRIPT, plugins_url( 'assets/js/scripts.js', WPSCAN_PLUGIN_FILE ), array( 'jquery' ) );
      wp_enqueue_script( 'pdfmake', plugins_url( 'assets/vendor/pdfmake/pdfmake.min.js', WPSCAN_PLUGIN_FILE ), array( self::WPSCAN_SCRIPT ) );
      wp_enqueue_script( self::WPSCAN_SCRIPT . '-download-report', plugins_url( 'assets/js/download-report.js', WPSCAN_PLUGIN_FILE ), array( 'pdfmake' ) );

      $local_array = array(
          'ajaxurl'       => admin_url( 'admin-ajax.php' ),
          'action_check'  => self::WPSCAN_ACTION_CHECK,
          'action_cron'   => self::WPSCAN_TRANSIENT_CRON,
          'ajax_nonce'    => wp_create_nonce( self::WPSCAN_SCRIPT ),
          'doing_cron'    => get_transient( self::WPSCAN_TRANSIENT_CRON ) ? 'YES' : 'NO'
      );

      wp_localize_script( self::WPSCAN_SCRIPT, 'local', $local_array );
    }

  }

  /*
  * Return the total of vulnerabilities found or -1 if errors
  */
  static public function get_total() {

    $report = self::$report;

    if ( empty( $report ) )
      return 0;

    $total = 0;
    $total += $report['wordpress']['total'];
    $total += $report['plugins']['total'];
    $total += $report['themes']['total'];

    return $total;

  }

  /*
  * Create a menu on Tools section
  */
  static public function menu() {

    $total = self::get_total();
    $count = $total > 0 ? ' <span class="update-plugins">' . $total . '</span>' : null;

    add_menu_page(
      'WPScan',
      'WPScan' . $count,
      self::WPSCAN_ROLE,
      'wpscan',
      array( 'WPScan_Report', 'page' ),
      self::$plugin_url . 'assets/svg/menu-icon.svg',
      null
    );

  }

  /*
  * Include a shortcut on Plugins Page
  *
  * @param array $links - Array of links provided by the filter
  * @return array
  */
  static public function add_action_links( $links ) {

    $links[] = '<a href="' . admin_url( 'admin.php?page=wpscan' ) . '">' . __( 'View' ) . '</a>';

    return $links;

  }

  /*
  * Get the WPScan plugin version.
  */
  static public function wpscan_plugin_version() {
    
    if( !function_exists('get_plugin_data') ){
      require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }

    return get_plugin_data( self::$plugin_dir . 'wpscan.php' )['Version'];

  }

  /*
  * Get information from the API
  * Return the JSON object or the code header.
  */
  static public function api_get( $endpoint, $api_token = null ) {

    if ( empty( $api_token ) )
      $api_token = get_option( self::OPT_API_TOKEN );

    // make sure endpoint starts with a slash
    if ( substr( $endpoint, 0, 1 ) !== "/" ) {
      $endpoint = '/' . $endpoint;
    }

    $args = array(
      'headers' => array(
        'Authorization' => 'Token token=' . $api_token,
        // Keep this lowercase to make older WordPress versions happy
        'user-agent'    => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url() . ' WPScan/' . self::wpscan_plugin_version()
      )
    );

    // Hook before the request
    do_action( 'wpscan/api/get/before' , $endpoint);

    // Start the request
    $response = wp_remote_get( WPSCAN_API_URL . $endpoint, $args );
    $code = wp_remote_retrieve_response_code( $response );
    
    // Hook after the request
    do_action( 'wpscan/api/get/after', $endpoint, $response );

    if ( $code == 200 ) {
      $body = wp_remote_retrieve_body( $response );
      return json_decode( $body );
    }

    return $code;

  }

  /*
  * Function to start checking right now
  */
  static public function check_now() {

    if ( get_transient( self::WPSCAN_TRANSIENT_CRON ) || empty( get_option( self::OPT_API_TOKEN ) ) )
      return;

    set_transient( self::WPSCAN_TRANSIENT_CRON, time() );
    self::verify();
    delete_transient( self::WPSCAN_TRANSIENT_CRON );

    // Notify by mail when solicited
    WPScan_Notification::notify();

  }

  /*
  * Function to verify on WpScan Database for vulnerabilities
  */
  static public function verify() {

    // Suppports during WP Cron
    if ( ! function_exists( 'get_plugins' ) ) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    if ( ! function_exists( 'wp_get_themes' ) ) {
      require_once ABSPATH . 'wp-admin/includes/theme.php';
    }
    
    $ignored = get_option(self::OPT_IGNORE_ITEMS, []);

    $report = array();
    $errors = array();

    // Plugins
    $report['plugins'] = array();
    $report['plugins']['total'] = 0;
    foreach ( get_plugins() as $name => $details ) {
      $slug = self::get_plugin_slug( $name, $details );
      if ( isset($ignored['plugins'][$slug]) ) continue;
      $result = self::api_get( '/plugins/' . $slug );
      if ( is_object( $result ) ) {
        $report['plugins'][ $slug ]['vulnerabilities'] = self::get_vulnerabilities( $result, $details['Version'] );
        if ( isset($result->$slug->closed) ) {
          $report['plugins'][ $slug ]['closed'] = is_object($result->$slug->closed) ? true : false;
        } else {
          $report['plugins'][ $slug ]['closed'] = false;
        }
        $report['plugins']['total'] += count( $report['plugins'][ $slug ]['vulnerabilities'] );
      } elseif( $result ===  401 ) {
        array_push( $errors, 401 );
      } elseif( $result ===  403 ) {
        array_push( $errors, 403 );
      } elseif( $result ===  404 ) {
        $report['plugins'][ $slug ]['not_found'] = true;
      }
    }

    // Themes
    $report['themes'] = array();
    $report['themes']['total'] = 0;
    $theme_options = array(
      'errors'  => null,
      'allowed' => null,
      'blog_id' => 0,
    );

    foreach ( wp_get_themes( $theme_options ) as $name => $details ) {
      $slug = self::get_theme_slug( $name, $details );
      if ( isset($ignored['themes'][$slug]) ) continue;
      $result = self::api_get( '/themes/' . $slug );
      if ( is_object( $result ) ) {
        $report['themes'][ $slug ]['vulnerabilities'] = self::get_vulnerabilities( $result, $details['Version'] );
        if ( isset($result->$slug->closed) ) {
          $report['themes'][ $slug ]['closed'] = is_object($result->$slug->closed) ? true : false;
        } else {
          $report['themes'][ $slug ]['closed'] = false;
        }
        $report['themes']['total'] += count( $report['themes'][ $slug ]['vulnerabilities'] );
      } elseif( $result ===  401 ) {
        array_push( $errors, 401 );
      } elseif( $result ===  403 ) {
        array_push( $errors, 403 );
      } elseif( $result ===  404 ) {
        $report['themes'][ $slug ]['not_found'] = true;
      }
    }

    // WordPress
    $report['wordpress'] = array();
    $report['wordpress']['total'] = 0;
    if ( !isset($ignored['wordpress']) ) {
      $version = get_bloginfo( 'version' );
      $result = self::api_get( '/wordpresses/' . str_replace( '.', '', $version ) );
      if ( is_object( $result ) ) {
        $report['wordpress'][ $version ]['vulnerabilities'] = self::get_vulnerabilities( $result, $version );
        $report['wordpress']['total'] = count( $report['wordpress'][ $version ]['vulnerabilities'] );
      } elseif( $result ===  401 ) {
        array_push( $errors, 401 );
      } elseif( $result ===  403 ) {
        array_push( $errors, 403 );
      }
    }
    // Caching
    $report['cache'] = strtotime( current_time( 'mysql' ) );

    // Errors
    $errors = array_unique($errors);
    if ( sizeof( $errors ) > 0 ) {
      $report['error'] = array();
    }
    foreach ( $errors as $err ) {
      // $err should NEVER contain user input. Otherwise you need to change the
      // implementation in class-summary.php to use esc_html() (but this will stop the links below to work)
      switch ($err) {
        case 401:
          array_push( $report['error'], __( 'Your API Token expired', 'wpscan' ) );
          break;
        case 403:
          array_push( $report['error'], sprintf( '%s <a href="%s" target="_blank">%s</a>.', __( 'You hit our free API usage limit. To increase your daily API limit please upgrade to paid usage from your', 'wpscan' ), WPSCAN_PROFILE_URL, __( 'WPScan profile page', 'wpscan' ) ) );
          break;
        default:
          array_push( $report['error'], sprintf( __( 'Error %s occurred on calling API', 'wpscan' ), $err ) );
          break;
      }
    }

    // Saving
    update_option( self::OPT_REPORT, $report, true );
    self::$report = $report;

  }

  /*
  * Filter vulnerability list from WPScan
  *
  * @param array $data - Report data for the element to check
  * @param string $version - Installed version
  * @return string
  */
  static public function get_vulnerabilities( $data, $version ) {
    
    $list = array();
    $key = key( $data );

    if ( empty( $data->$key->vulnerabilities ) ) {
      return $list;
    }

    // Trim and remove potential leading 'v'
    $version = ltrim(trim($version), 'v');

    foreach ( $data->$key->vulnerabilities as $item ) {
      if ( $item->fixed_in ) {
        if ( version_compare( $version, $item->fixed_in, '<' ) ) {
          $list[] = $item;
        }
      } else {
        $list[] = $item;
      }
    }

    return $list;

  }

  /*
  * Get vulnerability title
  *
  * @param string $vulnerability - element array
  * @return string
  */
  static public function get_vulnerability_title( $vulnerability ) {
    $title = esc_html( $vulnerability->title ) . ' - ';
    $title .= empty( $vulnerability->fixed_in ) ? __( 'Not fixed', 'wpscan' ) : sprintf( __( 'Fixed in version %s', 'wpscan' ), $vulnerability->fixed_in );

    return $title;
  }

  /*
  * Get the plugin slug for the given name
  *
  * @param string $name - plugin name "folder/file.php" or "hello.php"
  * @param string $details
  * @return string
  */
  static public function get_plugin_slug( $name, $details ) {

    $name = self::get_name( $name );
    $name = self::get_real_slug( $name, $details['PluginURI'] );
    
    return sanitize_title($name);

  }

  /*
  * Get the theme slug for the given name
  *
  * @param string $name - plugin name "folder/file.php" or "hello.php"
  * @param string $details
  * @return string
  */
  static public function get_theme_slug( $name, $details ) {

    $name = self::get_name( $name );
    $name = self::get_real_slug( $name, $details['ThemeURI'] );
    
    return sanitize_title($name);

  }

  /*
  * Get the plugin/theme name
  *
  * @param string $name - plugin name "folder/file.php" or "hello.php"
  * @return string
  */
  static private function get_name( $name ) {

    return strstr( $name, '/' ) ? dirname($name) : $name;

  }

  /*
  * The name returned by get_plugins or get_themes is not always the real slug
  * If the pluginURI is a wordpress url, we take the slug from there
  * this also fixes folder renames on plugins if the readme is correct.
  *
  * @param string $name - asset name from get_plugins or wp_get_themes
  * @param string $url - either the value or ThemeURI or PluginURI
  * @return string
  */
  static private function get_real_slug( $name, $url ) {
    $slug = $name;
    $match = preg_match( '/https?:\/\/wordpress\.org\/(?:extend\/)?(?:plugins|themes)\/([^\/]+)\/?/', $url, $matches );
    if ( $match === 1 ) {
      $slug = $matches[1];
    }
    return sanitize_title($slug);
  }

  /*
  * Is interval scanning is disabled?
  * Users can disable the automated scanning by setting WPSCAN_DISABLE_SCANNING_INTERVAL constant to true.
  */
  static public function is_interval_scanning_disabled() {
    if ( defined('WPSCAN_DISABLE_SCANNING_INTERVAL') ) {
      return WPSCAN_DISABLE_SCANNING_INTERVAL;
    } else {
      return false;
    }
  }
}
