<?php

/**
* Class Limit_Login_Attempts
*/
class Limit_Login_Attempts {

	public $default_options = array(
        'gdpr'              => 0,
		'gdpr_message'      => '',

		/* Are we behind a proxy? */
		'client_type'        => LLA_DIRECT_ADDR,

		/* Lock out after this many tries */
		'allowed_retries'    => 4,

		/* Lock out for this many seconds */
		'lockout_duration'   => 1200, // 20 minutes

		/* Long lock out after this many lockouts */
		'allowed_lockouts'   => 4,

		/* Long lock out for this many seconds */
		'long_duration'      => 86400, // 24 hours,

		/* Reset failed attempts after this many seconds */
		'valid_duration'     => 86400, // 12 hours

		/* Also limit malformed/forged cookies? */
		'cookies'            => true,

		/* Notify on lockout. Values: '', 'log', 'email', 'log,email' */
		'lockout_notify'     => 'email',

		/* If notify by email, do so after this number of lockouts */
		'notify_email_after' => 3,

		'review_notice_shown' => false,
		'enable_notify_notice_shown' => false,

		'whitelist'           => array(),
		'whitelist_usernames' => array(),
		'blacklist'           => array(),
		'blacklist_usernames' => array(),

        'active_app'       => 'local',
        'app_config'       => '',
        'show_top_level_menu_item' => true
	);
	/**
	* Admin options page slug
	* @var string
	*/
	private $_options_page_slug = 'limit-login-attempts';

	/**
	 * @var string
	 */
	private $_welcome_page_slug = 'llar-welcome';

	/**
	* Errors messages
	*
	* @var array
	*/
	public $_errors = array();

	/**
	* Additional login errors messages that we need to show
	*
	* @var array
	*/
	public $other_login_errors = array();

	/**
	 * @var null
	 */
	private $use_local_options = null;

	/**
     * Current app object
     *
	 * @var LLAR_App
	 */
	public $app = null;

	public function __construct() {

	    $this->default_options['gdpr_message'] = __( 'By proceeding you understand and give your consent that your IP address and browser information might be processed by the security plugins installed on this site.', 'limit-login-attempts-reloaded' );

		$this->hooks_init();
        $this->app_init();
	}

	/**
	* Register wp hooks and filters
	*/
	public function hooks_init() {
		add_action( 'plugins_loaded', array( $this, 'setup' ), 9999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'login_page_enqueue' ) );
		add_filter( 'limit_login_whitelist_ip', array( $this, 'check_whitelist_ips' ), 10, 2 );
		add_filter( 'limit_login_whitelist_usernames', array( $this, 'check_whitelist_usernames' ), 10, 2 );
		add_filter( 'limit_login_blacklist_ip', array( $this, 'check_blacklist_ips' ), 10, 2 );
		add_filter( 'limit_login_blacklist_usernames', array( $this, 'check_blacklist_usernames' ), 10, 2 );

		add_filter( 'illegal_user_logins', array( $this, 'register_user_blacklist' ), 999 );

		// TODO: Temporary turn off the holiday warning.
		//add_action( 'admin_notices', array( $this, 'show_enable_notify_notice' ) );

		add_action( 'admin_notices', array( $this, 'show_leave_review_notice' ) );
		add_action( 'wp_ajax_dismiss_review_notice', array( $this, 'dismiss_review_notice_callback' ) );
		add_action( 'wp_ajax_dismiss_notify_notice', array( $this, 'dismiss_notify_notice_callback' ) );
		add_action( 'wp_ajax_enable_notify', array( $this, 'enable_notify_callback' ) );
		add_action( 'wp_ajax_app_config_save', array( $this, 'app_config_save_callback' ));
		add_action( 'wp_ajax_app_setup', array( $this, 'app_setup_callback' ) );
		add_action( 'wp_ajax_app_log_action', array( $this, 'app_log_action_callback' ) );
		add_action( 'wp_ajax_app_load_log', array( $this, 'app_load_log_callback' ) );
		add_action( 'wp_ajax_app_load_lockouts', array( $this, 'app_load_lockouts_callback' ) );
		add_action( 'wp_ajax_app_load_acl_rules', array( $this, 'app_load_acl_rules_callback' ) );
		add_action( 'wp_ajax_app_load_country_access_rules', array( $this, 'app_load_country_access_rules_callback' ) );
		add_action( 'wp_ajax_app_toggle_country', array( $this, 'app_toggle_country_callback' ) );
		add_action( 'wp_ajax_app_country_rule', array( $this, 'app_country_rule_callback' ) );
		add_action( 'wp_ajax_app_acl_add_rule', array( $this, 'app_acl_add_rule_callback' ) );
		add_action( 'wp_ajax_app_acl_remove_rule', array( $this, 'app_acl_remove_rule_callback' ) );

		add_action( 'admin_print_scripts-toplevel_page_limit-login-attempts', array( $this, 'load_admin_scripts' ) );
		add_action( 'admin_print_scripts-settings_page_limit-login-attempts', array( $this, 'load_admin_scripts' ) );

		add_action( 'admin_init', array( $this, 'welcome_page_redirect' ), 9999 );
		add_action( 'admin_head', array( $this, 'welcome_page_hide_menu' ) );

		add_action( 'login_footer', array( $this, 'login_page_gdpr_message' ) );

		register_activation_hook( LLA_PLUGIN_FILE, array( $this, 'activation' ) );
	}

	/**
	 * Runs when the plugin is activated
	 */
	public function activation() {

		set_transient( 'llar_welcome_redirect', true, 30 );
	}

	/**
	 * Redirect to Welcome page after installed
	 */
	public function welcome_page_redirect() {

	    if( ! get_transient( 'llar_welcome_redirect' ) || isset( $_GET['activate-multi'] ) || is_network_admin() ) {
	        return;
        }

		delete_transient( 'llar_welcome_redirect' );

	    wp_redirect( admin_url( 'index.php?page=' . $this->_welcome_page_slug ) );
	    exit();
    }

    public function welcome_page_hide_menu() {

		remove_submenu_page( 'index.php', $this->_welcome_page_slug );
    }

	/**
	* Hook 'plugins_loaded'
	*/
	public function setup() {

		if( ! ( $activation_timestamp = $this->get_option( 'activation_timestamp' ) ) ) {

			// Write time when the plugin is activated
			$this->update_option( 'activation_timestamp', time() );
		}

		if( ! ( $activation_timestamp = $this->get_option( 'notice_enable_notify_timestamp' ) ) ) {

			// Write time when the plugin is activated
			$this->update_option( 'notice_enable_notify_timestamp', strtotime( '-32 day' ) );
		}

		// Load languages files
		load_plugin_textdomain( 'limit-login-attempts-reloaded', false, plugin_basename( dirname( __FILE__ ) ) . '/../languages' );

		// Check if installed old plugin
		$this->check_original_installed();

		if ( is_multisite() )
			require_once ABSPATH.'wp-admin/includes/plugin.php';

		$this->network_mode = is_multisite() && is_plugin_active_for_network('limit-login-attempts-reloaded/limit-login-attempts-reloaded.php');


		if ( $this->network_mode )
		{
			$this->allow_local_options = get_site_option( 'limit_login_allow_local_options', false );
			$this->use_local_options = $this->allow_local_options && get_option( 'limit_login_use_local_options', false );
		}
		else
		{
			$this->allow_local_options = true;
			$this->use_local_options = true;
		}


		// Setup default plugin options
		//$this->sanitize_options();

		add_action( 'wp_login_failed', array( $this, 'limit_login_failed' ) );
		add_filter( 'wp_authenticate_user', array( $this, 'wp_authenticate_user' ), 99999, 2 );

		add_filter( 'shake_error_codes', array( $this, 'failure_shake' ) );
		add_action( 'login_errors', array( $this, 'fixup_error_messages' ) );

		if ( $this->network_mode )
			add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ) );

		if ( $this->allow_local_options )
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		// Add notices for XMLRPC request
		add_filter( 'xmlrpc_login_error', array( $this, 'xmlrpc_error_messages' ) );

		// Add notices to woocommerce login page
		add_action( 'wp_head', array( $this, 'add_wc_notices' ) );

		/*
		* This action should really be changed to the 'authenticate' filter as
		* it will probably be deprecated. That is however only available in
		* later versions of WP.
		*/
		add_action( 'wp_authenticate', array( $this, 'track_credentials' ), 10, 2 );
		add_action( 'authenticate', array( $this, 'authenticate_filter' ), 5, 3 );

		/**
		 * BuddyPress unactivated user account message
		 */
		add_action( 'authenticate', array( $this, 'bp_authenticate_filter' ), 35, 3 );

		add_action('wp_ajax_limit-login-unlock', array( $this, 'ajax_unlock' ) );

		add_filter( 'plugin_action_links_' . LLA_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
	}

	public function login_page_gdpr_message() {

	    if( ! $this->get_option( 'gdpr' ) || isset( $_REQUEST['interim-login'] ) ) return;

	    ?>
            <div id="llar-login-page-gdpr">
                <div class="llar-login-page-gdpr__message"><?php echo do_shortcode( stripslashes( $this->get_option( 'gdpr_message' ) ) ); ?></div>
            </div>
        <?php
    }

	public function add_action_links( $actions ) {

		$actions = array_merge( array(
			'<a href="' . $this->get_options_page_uri( 'settings' ) . '">' . __( 'Settings', 'limit-login-attempts-reloaded' ) . '</a>',
			'<a href="https://www.limitloginattempts.com/info.php?from=plugin-plugins" target="_blank" style="font-weight: bold;">' . __( 'Premium Support', 'limit-login-attempts-reloaded' ) . '</a>',
		), $actions );

		return $actions;
	}

	public function app_init() {

		if( $this->get_option( 'active_app' ) === 'custom' && $config = $this->get_custom_app_config() ) {

			$this->app = new LLAR_App( $config );
		}
    }

	public function load_admin_scripts() {

		wp_enqueue_script('jquery-ui-accordion');
		wp_enqueue_style('llar-jquery-ui', LLA_PLUGIN_URL.'assets/css/jquery-ui.css');

		wp_enqueue_style( 'llar-charts', LLA_PLUGIN_URL.'assets/css/Chart.min.css' );
		wp_enqueue_script( 'llar-charts', LLA_PLUGIN_URL . 'assets/js/Chart.bundle.min.js' );
		wp_enqueue_script( 'llar-charts-gauge', LLA_PLUGIN_URL . 'assets/js/chartjs-gauge.js' );
	}

	public function check_whitelist_ips( $allow, $ip ) {
		return $this->ip_in_range( $ip, (array) $this->get_option( 'whitelist' ) );
	}

	public function check_whitelist_usernames( $allow, $username ) {
		return in_array( $username, (array) $this->get_option( 'whitelist_usernames' ) );
	}

	public function check_blacklist_ips( $allow, $ip ) {
		return $this->ip_in_range( $ip, (array) $this->get_option( 'blacklist' ) );
	}

	public function check_blacklist_usernames( $allow, $username ) {
		return in_array( $username, (array) $this->get_option( 'blacklist_usernames' ) );
	}

	public function ip_in_range( $ip, $list ) {

	    foreach ( $list as $range ) {

			$range = array_map('trim', explode('-', $range) );
			if ( count( $range ) == 1 ) {

			    // CIDR
			    if( strpos( $range[0], '/' ) !== false && LLA_Helpers::check_ip_cidr( $ip, $range[0] ) ) {

			        return true;
				}
			    // Single IP
			    else if ( (string)$ip === (string)$range[0] ) {

					 return true;
				}

			} else {

				$low = ip2long( $range[0] );
				$high = ip2long( $range[1] );
				$needle = ip2long( $ip );

				if ( $low === false || $high === false || $needle === false )
					continue;

				$low = (float)sprintf("%u",$low);
				$high = (float)sprintf("%u",$high);
				$needle = (float)sprintf("%u",$needle);

				if ( $needle >= $low && $needle <= $high )
					return true;
			}
		}

		return false;
	}

	/**
	 * @param $blacklist
	 * @return array|null
	 */
	public function register_user_blacklist($blacklist) {

		$black_list_usernames = $this->get_option( 'blacklist_usernames' );

		if(!empty($black_list_usernames) && is_array($black_list_usernames)) {
			$blacklist += $black_list_usernames;
		}

		return $blacklist;
	}

	/**
	* @param $error IXR_Error
	*
	* @return IXR_Error
	*/
	public function xmlrpc_error_messages( $error ) {

		if ( ! class_exists( 'IXR_Error' ) ) {
			return $error;
		}

		if( $login_error = $this->get_message() ) {

			return new IXR_Error( 403, strip_tags( $login_error ) );
        }

		return $error;
	}

	/**
	* Errors on WooCommerce account page
	*/
	public function add_wc_notices() {

		global $limit_login_just_lockedout, $limit_login_nonempty_credentials, $limit_login_my_error_shown;

		if ( ! function_exists( 'is_account_page' ) || ! function_exists( 'wc_add_notice' ) || !$limit_login_nonempty_credentials ) {
			return;
		}

		/*
		* During lockout we do not want to show any other error messages (like
		* unknown user or empty password).
		*/
		if ( empty( $_POST ) && ! $this->is_limit_login_ok() && ! $limit_login_just_lockedout ) {
			if ( is_account_page() ) {
				wc_add_notice( $this->error_msg(), 'error' );
			}
		}

	}

	/**
	* @param $user
	* @param $username
	* @param $password
	*
	* @return WP_Error | WP_User
	*/
	public function authenticate_filter( $user, $username, $password ) {

		if ( ! empty( $username ) && ! empty( $password ) ) {

			if( $this->app && $response = $this->app->acl_check( array(
			        'ip'        => $this->get_all_ips(),
                    'login'     => $username,
                    'gateway'   => $this->detect_gateway()
                ) ) ) {

			    if( $response['result'] === 'deny' ) {

					remove_filter( 'login_errors', array( $this, 'fixup_error_messages' ) );
					remove_filter( 'wp_login_failed', array( $this, 'limit_login_failed' ) );
					remove_filter( 'wp_authenticate_user', array( $this, 'wp_authenticate_user' ), 99999 );

			        // Remove default WP authentication filters
					remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
					remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );

					$err = __( '<strong>ERROR</strong>: Too many failed login attempts.', 'limit-login-attempts-reloaded' );

					$time_left = ( !empty( $response['time_left'] ) ) ? $response['time_left'] : 0;
					if( $time_left ) {

						if ( $time_left > 60 ) {
							$time_left = ceil( $time_left / 60 );
							$err .= ' ' . sprintf( _n( 'Please try again in %d hour.', 'Please try again in %d hours.', $time_left, 'limit-login-attempts-reloaded' ), $time_left );
						} else {
							$err .= ' ' . sprintf( _n( 'Please try again in %d minute.', 'Please try again in %d minutes.', $time_left, 'limit-login-attempts-reloaded' ), $time_left );
						}
                    }

					$this->app->add_error( $err );

					$user = new WP_Error();
					$user->add( 'username_blacklisted', $err );

					if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {

						header('HTTP/1.0 403 Forbidden');
						exit;
					}
                }
                else if( $response['result'] === 'pass' ) {

					remove_filter( 'login_errors', array( $this, 'fixup_error_messages' ) );
					remove_filter( 'wp_login_failed', array( $this, 'limit_login_failed' ) );
					remove_filter( 'wp_authenticate_user', array( $this, 'wp_authenticate_user' ), 99999 );
                }

            } else {

				$ip = $this->get_address();

				// Check if username is blacklisted
				if ( ! $this->is_username_whitelisted( $username ) && ! $this->is_ip_whitelisted( $ip ) &&
					( $this->is_username_blacklisted( $username ) || $this->is_ip_blacklisted( $ip ) )
				) {

					remove_filter( 'login_errors', array( $this, 'fixup_error_messages' ) );
					remove_filter( 'wp_login_failed', array( $this, 'limit_login_failed' ) );
					remove_filter( 'wp_authenticate_user', array( $this, 'wp_authenticate_user' ), 99999 );

					// Remove default WP authentication filters
					remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
					remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );

					$user = new WP_Error();
					$user->add( 'username_blacklisted', "<strong>ERROR:</strong> Too many failed login attempts." );

					if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {

						header('HTTP/1.0 403 Forbidden');
						exit;
                    }

				} elseif ( $this->is_username_whitelisted( $username ) || $this->is_ip_whitelisted( $ip ) ) {

					remove_filter( 'wp_login_failed', array( $this, 'limit_login_failed' ) );
					remove_filter( 'wp_authenticate_user', array( $this, 'wp_authenticate_user' ), 99999 );
					remove_filter( 'login_errors', array( $this, 'fixup_error_messages' ) );

				}
            }
		}

		return $user;
	}

	/**
     * BuddyPress unactivated user account message fix
     *
	 * @param $user
	 * @param $username
	 * @param $password
	 * @return mixed
	 */
	public function bp_authenticate_filter( $user, $username, $password ) {

		if ( ! empty( $username ) && ! empty( $password ) ) {

		    if(is_wp_error($user) && in_array('bp_account_not_activated', $user->get_error_codes()) ) {

				$this->other_login_errors[] = $user->get_error_message('bp_account_not_activated');
            }
		}
		return $user;
	}

	/**
	 * @return array
	 */
	public function get_all_ips() {

	    $ips = array();

		foreach ( $_SERVER as $key => $value ) {

			if( in_array( $key, array( 'SERVER_ADDR' ) ) ) continue;

			if( filter_var( $value, FILTER_VALIDATE_IP ) ) {

				$ips[$key] = $value;
			}
		}

		if( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && !array_key_exists( 'HTTP_X_FORWARDED_FOR', $ips ) ) {

			$ips['HTTP_X_FORWARDED_FOR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

		return $ips;
    }

	/**
	* Check if the original plugin is installed
	*/
	private function check_original_installed()
	{
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		if ( is_plugin_active('limit-login-attempts/limit-login-attempts.php') )
		{
			deactivate_plugins( 'limit-login-attempts/limit-login-attempts.php', true );
			//add_action('plugins_loaded', 'limit_login_setup', 99999);
			remove_action( 'plugins_loaded', 'limit_login_setup', 99999 );
		}
	}

	/**
	* Enqueue js and css
	*/
	public function enqueue() {

	    $plugin_data = get_plugin_data( LLA_PLUGIN_DIR . '/limit-login-attempts-reloaded.php' );

		wp_enqueue_style( 'lla-main', LLA_PLUGIN_URL . 'assets/css/limit-login-attempts.css', array(), $plugin_data['Version'] );
		wp_enqueue_script( 'lla-main', LLA_PLUGIN_URL . 'assets/js/limit-login-attempts.js', array(), $plugin_data['Version'] );

		if( !empty( $_REQUEST['page'] ) && $_REQUEST['page'] === $this->_welcome_page_slug ) {

			wp_enqueue_style( 'lla-jquery-confirm', LLA_PLUGIN_URL . 'assets/css/jquery-confirm.min.css' );
			wp_enqueue_script( 'lla-jquery-confirm', LLA_PLUGIN_URL . 'assets/js/jquery-confirm.min.js' );
        }

	}

	public function login_page_enqueue() {

	    $plugin_data = get_plugin_data( LLA_PLUGIN_DIR . '/limit-login-attempts-reloaded.php' );

		wp_enqueue_style( 'llar-login-page-styles', LLA_PLUGIN_URL . 'assets/css/login-page-styles.css', array(), $plugin_data['Version'] );
	}

	/**
	* Add admin options page
	*/
	public function network_admin_menu()
	{
		add_submenu_page( 'settings.php', 'Limit Login Attempts', 'Limit Login Attempts', 'manage_options', $this->_options_page_slug, array( $this, 'options_page' ) );
	}

	public function admin_menu() {

		if( $this->get_option( 'show_top_level_menu_item' ) ) {

			add_menu_page(
                'Limit Login Attempts',
                'Limit Login Attempts',
                'manage_options',
                $this->_options_page_slug,
                array( $this, 'options_page' ),
				'data:image/svg+xml;base64,' . base64_encode($this->get_svg_logo_content())
            );
        }

		add_options_page( 'Limit Login Attempts', 'Limit Login Attempts', 'manage_options', $this->_options_page_slug, array( $this, 'options_page' ) );
        
		add_dashboard_page(
            'Welcome to Limit Login Attempts Reloaded',
            'Limit Login Attempts Welcome',
            'manage_options',
            $this->_welcome_page_slug,
            array( $this, 'welcome_page' )
        );
	}

	public function get_svg_logo_content() {
	    return file_get_contents( LLA_PLUGIN_DIR . '/assets/img/logo.svg' );
    }

	/**
	 * Get the correct options page URI
	 *
	 * @param bool $tab
	 * @return mixed
	 */
	public function get_options_page_uri($tab = false)
	{

		if ( is_network_admin() )
			$uri = network_admin_url( 'settings.php?page=' . $this->_options_page_slug );
		else
		    $uri = menu_page_url( $this->_options_page_slug, false );

		if( !empty( $tab ) ) {

		    $uri = add_query_arg( 'tab', $tab, $uri );
        }

		return $uri;
	}

	/**
	* Get option by name
	*
	* @param $option_name
	*
	* @return null
	*/
	public function get_option( $option_name, $local = null )
	{
		if ( is_null( $local ) )
			$local = $this->use_local_options;

		$option = 'limit_login_'.$option_name;

		$func = $local ? 'get_option' : 'get_site_option';
		$value = $func( $option, null );

		if ( is_null( $value ) && isset( $this->default_options[ $option_name ] ) )
			$value = $this->default_options[ $option_name ];

		return $value;
	}

	public function update_option( $option_name, $value, $local = null )
	{
		if ( is_null( $local ) )
			$local = $this->use_local_options;

		$option = 'limit_login_'.$option_name;

		$func = $local ? 'update_option' : 'update_site_option';

		return $func( $option, $value );
	}

	public function add_option( $option_name, $value, $local=null )
	{
		if ( is_null( $local ) )
			$local = $this->use_local_options;

		$option = 'limit_login_'.$option_name;

		$func = $local ? 'add_option' : 'add_site_option';

		return $func( $option, $value, '', 'no' );
	}

	public function delete_option( $option_name, $local=null )
	{
		if ( is_null( $local ) )
			$local = $this->use_local_options;

		$option = 'limit_login_'.$option_name;

		$func = $local ? 'delete_option' : 'delete_site_option';

		return $func( $option );
	}

	/**
	* Setup main options
	*/
	public function sanitize_options()
	{
		$simple_int_options = array( 'allowed_retries', 'lockout_duration', 'valid_duration', 'allowed_lockouts', 'long_duration', 'notify_email_after');
		foreach ( $simple_int_options as $option )
		{
			$val = $this->get_option( $option );
			if ( (int)$val != $val || (int)$val <= 0 )
				$this->update_option( $option, 1 );
		}
		if ( $this->get_option('notify_email_after') > $this->get_option( 'allowed_lockouts' ) )
			$this->update_option( 'notify_email_after', $this->get_option( 'allowed_lockouts' ) );

		$args         = explode( ',', $this->get_option( 'lockout_notify' ) );
		$args_allowed = explode( ',', LLA_LOCKOUT_NOTIFY_ALLOWED );
		$new_args     = array_intersect( $args, $args_allowed );

		$this->update_option( 'lockout_notify', implode( ',', $new_args ) );

		$ctype = $this->get_option( 'client_type' );
		if ( $ctype != LLA_DIRECT_ADDR && $ctype != LLA_PROXY_ADDR )
			$this->update_option( 'client_type', LLA_DIRECT_ADDR );
	}

	/**
	* Check if it is ok to login
	*
	* @return bool
	*/
	public function is_limit_login_ok() {

		$ip = $this->get_address();

		/* Check external whitelist filter */
		if ( $this->is_ip_whitelisted( $ip ) ) {
			return true;
		}

		/* lockout active? */
		$lockouts = $this->get_option( 'lockouts' );

		return ( !is_array( $lockouts ) || !isset( $lockouts[ $ip ] ) || time() >= $lockouts[$ip] );
	}

	/**
	* Action when login attempt failed
	*
	* Increase nr of retries (if necessary). Reset valid value. Setup
	* lockout if nr of retries are above threshold. And more!
	*
	* A note on external whitelist: retries and statistics are still counted and
	* notifications done as usual, but no lockout is done.
	*
	* @param $username
	*/
	public function limit_login_failed( $username ) {

		if( $this->app && $response = $this->app->lockout_check( array(
				'ip'        => $this->get_all_ips(),
				'login'     => $username,
                'gateway'   => $this->detect_gateway()
            ) ) ) {

		    if( $response['result'] === 'allow' ) {

				$this->app->add_error(
					sprintf( _n( "<strong>%d</strong> attempt remaining.", "<strong>%d</strong> attempts remaining.", $response['attempts_left'], 'limit-login-attempts-reloaded' ), $response['attempts_left'] )
				);
            } elseif( $response['result'] === 'deny' ) {

		        global $limit_login_just_lockedout;
		        $limit_login_just_lockedout = true;

		        $err = __( '<strong>ERROR</strong>: Too many failed login attempts.', 'limit-login-attempts-reloaded' );

		        $time_left = ( !empty( $response['time_left'] ) ) ? $response['time_left'] : 0;
				if ( $time_left > 60 ) {
					$time_left = ceil( $time_left / 60 );
					$err .= ' ' . sprintf( _n( 'Please try again in %d hour.', 'Please try again in %d hours.', $time_left, 'limit-login-attempts-reloaded' ), $time_left );
				} else {
					$err .= ' ' . sprintf( _n( 'Please try again in %d minute.', 'Please try again in %d minutes.', $time_left, 'limit-login-attempts-reloaded' ), $time_left );
				}

		        $this->app->add_error( $err );
            }

		} else {

			$ip = $this->get_address();

			/* if currently locked-out, do not add to retries */
			$lockouts = $this->get_option( 'lockouts' );

			if ( ! is_array( $lockouts ) ) {
				$lockouts = array();
			}

			if ( isset( $lockouts[ $ip ] ) && time() < $lockouts[ $ip ] ) {
				return;
			}

			/* Get the arrays with retries and retries-valid information */
			$retries = $this->get_option( 'retries' );
			$valid   = $this->get_option( 'retries_valid' );
			$retries_stats = $this->get_option( 'retries_stats' );

			if ( ! is_array( $retries ) ) {
				$retries = array();
				$this->add_option( 'retries', $retries );
			}

			if ( ! is_array( $valid ) ) {
				$valid = array();
				$this->add_option( 'retries_valid', $valid );
			}

			if ( ! is_array( $retries_stats ) ) {
				$retries_stats = array();
				$this->add_option( 'retries_stats', $retries_stats );
			}

			$date_key = date_i18n( 'Y-m-d' );
            if(!empty($retries_stats[$date_key])) {

				$retries_stats[$date_key]++;
			} else {

				$retries_stats[$date_key] = 1;
            }
			$this->update_option( 'retries_stats', $retries_stats );

			/* Check validity and add one to retries */
			if ( isset( $retries[ $ip ] ) && isset( $valid[ $ip ] ) && time() < $valid[ $ip ]) {
				$retries[ $ip ] ++;
			} else {
				$retries[ $ip ] = 1;
			}
			$valid[ $ip ] = time() + $this->get_option( 'valid_duration' );

			/* lockout? */
			if ( $retries[ $ip ] % $this->get_option( 'allowed_retries' ) != 0 ) {
				/*
				* Not lockout (yet!)
				* Do housecleaning (which also saves retry/valid values).
				*/
				$this->cleanup( $retries, null, $valid );

				return;
			}

			/* lockout! */
			$whitelisted = $this->is_ip_whitelisted( $ip );
			$retries_long = $this->get_option( 'allowed_retries' ) * $this->get_option( 'allowed_lockouts' );

			/*
			* Note that retries and statistics are still counted and notifications
			* done as usual for whitelisted ips , but no lockout is done.
			*/
			if ( $whitelisted ) {
				if ( $retries[ $ip ] >= $retries_long ) {
					unset( $retries[ $ip ] );
					unset( $valid[ $ip ] );
				}
			} else {
				global $limit_login_just_lockedout;
				$limit_login_just_lockedout = true;

				/* setup lockout, reset retries as needed */
				if ( (isset($retries[ $ip ]) ? $retries[ $ip ] : 0) >= $retries_long ) {
					/* long lockout */
					$lockouts[ $ip ] = time() + $this->get_option( 'long_duration' );
					unset( $retries[ $ip ] );
					unset( $valid[ $ip ] );
				} else {
					/* normal lockout */
					$lockouts[ $ip ] = time() + $this->get_option( 'lockout_duration' );
				}
			}

			/* do housecleaning and save values */
			$this->cleanup( $retries, $lockouts, $valid );

			/* do any notification */
			$this->notify( $username );

			/* increase statistics */
			$total = $this->get_option( 'lockouts_total' );
			if ( $total === false || ! is_numeric( $total ) ) {
				$this->add_option( 'lockouts_total', 1 );
			} else {
				$this->update_option( 'lockouts_total', $total + 1 );
			}
		}
	}

	/**
	 * Handle notification in event of lockout
	 *
	 * @param $user
	 * @return bool|void
	 */
	public function notify( $user ) {

		if( is_object( $user ) ) {
            return false;
		}

		$this->notify_log( $user );

		$args = explode( ',', $this->get_option( 'lockout_notify' ) );

		if ( empty( $args ) ) {
			return;
		}

		if( in_array( 'email', $args ) ) {
			$this->notify_email( $user );
		}
	}

	/**
	* Email notification of lockout to admin (if configured)
	*
	* @param $user
	*/
	public function notify_email( $user ) {
		$ip          = $this->get_address();
		$whitelisted = $this->is_ip_whitelisted( $ip );

		$retries = $this->get_option( 'retries' );
		if ( ! is_array( $retries ) ) {
			$retries = array();
		}

		/* check if we are at the right nr to do notification */
		if ( isset( $retries[ $ip ] )
                &&
            ( ( intval($retries[$ip]) / $this->get_option( 'allowed_retries' ) ) % $this->get_option( 'notify_email_after' ) ) != 0 ) {

			return;
		}

		/* Format message. First current lockout duration */
		if ( !isset( $retries[ $ip ] ) ) {
			/* longer lockout */
			$count    = $this->get_option( 'allowed_retries' )
						* $this->get_option( 'allowed_lockouts' );
			$lockouts = $this->get_option( 'allowed_lockouts' );
			$time     = round( $this->get_option( 'long_duration' ) / 3600 );
			$when     = sprintf( _n( '%d hour', '%d hours', $time, 'limit-login-attempts-reloaded' ), $time );
		} else {
			/* normal lockout */
			$count    = $retries[ $ip ];
			$lockouts = floor( ($count) / $this->get_option( 'allowed_retries' ) );
			$time     = round( $this->get_option( 'lockout_duration' ) / 60 );
			$when     = sprintf( _n( '%d minute', '%d minutes', $time, 'limit-login-attempts-reloaded' ), $time );
		}

		if( $custom_admin_email = $this->get_option( 'admin_notify_email' ) ) {

			$admin_email = $custom_admin_email;
		} else {

			$admin_email = $this->use_local_options ? get_option( 'admin_email' ) : get_site_option( 'admin_email' );
		}

		$admin_name = '';

		global $wpdb;

		$res = $wpdb->get_col( $wpdb->prepare( "
                SELECT u.display_name
                FROM $wpdb->users AS u
                LEFT JOIN $wpdb->usermeta AS m ON u.ID = m.user_id
                WHERE u.user_email = %s
                AND m.meta_key LIKE 'wp_capabilities'
                AND m.meta_value LIKE '%administrator%'",
                $admin_email
            )
        );

		if( $res ) {
		    $admin_name = ' ' . $res[0];
        }

        $site_domain = str_replace( array( 'http://', 'https://' ), '', home_url() );
		$blogname = $this->use_local_options ? get_option( 'blogname' ) : get_site_option( 'site_name' );
		$blogname = htmlspecialchars_decode( $blogname, ENT_QUOTES );

        $subject = sprintf(
            __( "[%s] Failed WordPress login attempt by IP %s", 'limit-login-attempts-reloaded' ),
            $blogname,
            $ip 
        );

        $message = __(
                '<p>Hello%1$s,</p>
<p>%2$d failed login attempts (%3$d lockout(s)) from IP <b>%4$s</b><br>
Last user attempted: <b>%5$s</b><br>
IP was blocked for %6$s</p>
<p>This notification was sent automatically via Limit Login Attempts Reloaded Plugin. 
<b>This is installed on your %7$s WordPress site. Please login to your WordPress dashboard to view more info.</b></p>
<p>Under Attack? Try our <a href="%8$s" target="_blank">advanced protection</a>. 
Have Questions? Visit our <a href="%9$s" target="_blank">help section</a>.</p>', 'limit-login-attempts-reloaded' );

		$plugin_data = get_plugin_data( LLA_PLUGIN_DIR . '/limit-login-attempts-reloaded.php' );

        $message = sprintf(
            $message,
			$admin_name,
			$count,
            $lockouts,
            $ip,
			$user,
            $when,
			$site_domain,
			'https://www.limitloginattempts.com/info.php?from=plugin-lockout-email&v='.$plugin_data['Version'],
			'https://www.limitloginattempts.com/resources/?from=plugin-lockout-email'
        );

		if( LLA_Helpers::is_mu() ) {

			$message .= sprintf( __(
				'<p><i>This alert was sent by your website where Limit Login Attempts Reloaded free version 
is installed and you are listed as the admin. If you are a GoDaddy customer, the plugin is installed 
into a must-use (MU) folder. You can read more <a href="%s" target="_blank">here</a>.</i></p>', 'limit-login-attempts-reloaded' ),
				'https://www.limitloginattempts.com/how-to-tell-if-i-have-limit-login-attempts-reloaded-on-my-site-a-survival-guide-for-godaddy-customers/'
            );
		}

		$message .= sprintf( __(
            '<hr><a href="%s">Unsubscribe</a> from these notifications.', 'limit-login-attempts-reloaded' ),
			admin_url( 'options-general.php?page=limit-login-attempts&tab=settings' )
        );

		@wp_mail( $admin_email, $subject, $message, array( 'content-type: text/html' ) );
	}

	/**
	* Logging of lockout (if configured)
	*
	* @param $user_login
	*
	* @internal param $user
	*/
	public function notify_log( $user_login ) {

		if ( ! $user_login ) {
			return;
		}

		$log = $option = $this->get_option( 'logged' );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		$ip = $this->get_address();

		/* can be written much simpler, if you do not mind php warnings */
		if ( !isset( $log[ $ip ] ) )
			$log[ $ip ] = array();

		if ( !isset( $log[ $ip ][ $user_login ] ) )
			$log[ $ip ][ $user_login ] = array( 'counter' => 0 );

		elseif ( !is_array( $log[ $ip ][ $user_login ] ) )
			$log[ $ip ][ $user_login ] = array(
				'counter' => $log[ $ip ][ $user_login ],
			);

		$log[ $ip ][ $user_login ]['counter']++;
		$log[ $ip ][ $user_login ]['date'] = time();

		$log[ $ip ][ $user_login ]['gateway'] = $this->detect_gateway();

		if ( $option === false ) {
			$this->add_option( 'logged', $log );
		} else {
			$this->update_option( 'logged', $log );
		}
	}

	/**
	 * @return string
	 */
	public function detect_gateway() {

		$gateway = 'wp_login';

		if ( isset( $_POST['woocommerce-login-nonce'] ) ) {
			$gateway = 'wp_woo_login';
		} elseif ( isset( $GLOBALS['wp_xmlrpc_server'] ) && is_object( $GLOBALS['wp_xmlrpc_server'] ) ) {
			$gateway = 'wp_xmlrpc';
		}

		return $gateway;
    }

	/**
	* Check if IP is whitelisted.
	*
	* This function allow external ip whitelisting using a filter. Note that it can
	* be called multiple times during the login process.
	*
	* Note that retries and statistics are still counted and notifications
	* done as usual for whitelisted ips , but no lockout is done.
	*
	* Example:
	* function my_ip_whitelist($allow, $ip) {
	*    return ($ip == 'my-ip') ? true : $allow;
	* }
	* add_filter('limit_login_whitelist_ip', 'my_ip_whitelist', 10, 2);
	*
	* @param null $ip
	*
	* @return bool
	*/
	public function is_ip_whitelisted( $ip = null ) {

		if ( is_null( $ip ) ) {
			$ip = $this->get_address();
		}

		$whitelisted = apply_filters( 'limit_login_whitelist_ip', false, $ip );

		return ( $whitelisted === true );
	}

	public function is_username_whitelisted( $username ) {

		if ( empty( $username ) ) {
			return false;
		}

		$whitelisted = apply_filters( 'limit_login_whitelist_usernames', false, $username );

		return ( $whitelisted === true );
	}

	public function is_ip_blacklisted( $ip = null ) {

		if ( is_null( $ip ) ) {
			$ip = $this->get_address();
		}

		$blacklisted = apply_filters( 'limit_login_blacklist_ip', false, $ip );

		return ( $blacklisted === true );
	}

	public function is_username_blacklisted( $username ) {

		if ( empty( $username ) ) {
			return false;
		}

		$whitelisted = apply_filters( 'limit_login_blacklist_usernames', false, $username );

		return ( $whitelisted === true );
	}

	/**
	* Filter: allow login attempt? (called from wp_authenticate())
	*
	* @param $user WP_User
	* @param $password
	*
	* @return \WP_Error
	*/
	public function wp_authenticate_user( $user, $password ) {

	    if( is_wp_error( $user ) ) {
	        return $user;
        }

	    $user_login = '';

	    if( is_a( $user, 'WP_User' ) ) {
	        $user_login = $user->user_login;
        } else if( !empty($user) && !is_wp_error($user) ) {
            $user_login = $user;
        }

		if ( $this->check_whitelist_ips( false, $this->get_address() ) ||
			$this->check_whitelist_usernames( false, $user_login ) ||
			$this->is_limit_login_ok()
		) {

			return $user;
		}

		$error = new WP_Error();

		global $limit_login_my_error_shown;
		$limit_login_my_error_shown = true;

		if ( $this->is_username_blacklisted( $user_login ) || $this->is_ip_blacklisted( $this->get_address() ) ) {
			$error->add( 'username_blacklisted', "<strong>ERROR:</strong> Too many failed login attempts." );
		} else {
			// This error should be the same as in "shake it" filter below
			$error->add( 'too_many_retries', $this->error_msg() );
		}

		return $error;
	}

	/**
	* Filter: add this failure to login page "Shake it!"
	*
	* @param $error_codes
	*
	* @return array
	*/
	public function failure_shake( $error_codes ) {
		$error_codes[] = 'too_many_retries';
		$error_codes[] = 'username_blacklisted';

		return $error_codes;
	}

	/**
	* Keep track of if user or password are empty, to filter errors correctly
	*
	* @param $user
	* @param $password
	*/
	public function track_credentials( $user, $password ) {
		global $limit_login_nonempty_credentials;

		$limit_login_nonempty_credentials = ( ! empty( $user ) && ! empty( $password ) );
	}

	/**
	* Construct informative error message
	*
	* @return string
	*/
	public function error_msg() {
		$ip       = $this->get_address();
		$lockouts = $this->get_option( 'lockouts' );
        $a = $this->checkKey($lockouts, $ip);
        $b = $this->checkKey($lockouts, $this->getHash($ip));

		$msg = __( '<strong>ERROR</strong>: Too many failed login attempts.', 'limit-login-attempts-reloaded' ) . ' ';

		if (
            ! is_array( $lockouts ) ||
            ( ! isset( $lockouts[ $ip ] ) && ! isset( $lockouts[$this->getHash($ip)]) ) ||
            (time() >= $a && time() >= $b)
        ){
			/* Huh? No timeout active? */
			$msg .= __( 'Please try again later.', 'limit-login-attempts-reloaded' );

			return $msg;
		}

		$when = ceil( ( ($a > $b ? $a : $b) - time() ) / 60 );
		if ( $when > 60 ) {
			$when = ceil( $when / 60 );
			$msg .= sprintf( _n( 'Please try again in %d hour.', 'Please try again in %d hours.', $when, 'limit-login-attempts-reloaded' ), $when );
		} else {
			$msg .= sprintf( _n( 'Please try again in %d minute.', 'Please try again in %d minutes.', $when, 'limit-login-attempts-reloaded' ), $when );
		}

		return $msg;
	}

	/**
	* Add a message to login page when necessary
	*/
	public function add_error_message() {
		global $error, $limit_login_my_error_shown, $limit_login_nonempty_credentials;

		if ( ! $this->login_show_msg() || $limit_login_my_error_shown || !$limit_login_nonempty_credentials ) {
			return;
		}

		$msg = $this->get_message();

		if ( $msg != '' ) {
			$limit_login_my_error_shown = true;
			$error .= $msg;
		}

		return;
	}

	/**
	* Fix up the error message before showing it
	*
	* @param $content
	*
	* @return string
	*/
	public function fixup_error_messages( $content ) {
		global $limit_login_just_lockedout, $limit_login_nonempty_credentials, $limit_login_my_error_shown;

		$error_msg = $this->get_message();

		if ( $limit_login_nonempty_credentials ) {

			$content = '';

		    if($this->other_login_errors) {

                foreach ($this->other_login_errors as $msg) {
                    $content .= $msg . "<br />\n";
                }

            } else if( !$limit_login_just_lockedout ) {

				/* Replace error message, including ours if necessary */
				if( !empty( $_REQUEST['log'] ) && is_email( $_REQUEST['log'] ) ) {
					$content = __( '<strong>ERROR</strong>: Incorrect email address or password.', 'limit-login-attempts-reloaded' ) . "<br />\n";
				} else{
					$content = __( '<strong>ERROR</strong>: Incorrect username or password.', 'limit-login-attempts-reloaded' ) . "<br />\n";
				}
            }

			if ( $error_msg ) {

		        $content .= ( !empty( $content ) ) ? "<br />\n" : '';
				$content .= $error_msg . "<br />\n";
			}
		}

		return $content;
	}

	public function fixup_error_messages_wc( \WP_Error $error ) {
		$error->add( 1, __( 'WC Error' ) );
	}

	/**
	* Return current (error) message to show, if any
	*
	* @return string
	*/
	public function get_message() {

	    if( $this->app && $app_errors = $this->app->get_errors() ) {

	        return implode( '<br>', $app_errors);
        } else {

			/* Check external whitelist */
			if ( $this->is_ip_whitelisted() ) {
				return '';
			}

			/* Is lockout in effect? */
			if ( ! $this->is_limit_login_ok() ) {
				return $this->error_msg();
			}

			return $this->retries_remaining_msg();
        }
	}

	/**
	* Construct retries remaining message
	*
	* @return string
	*/
	public function retries_remaining_msg() {
		$ip      = $this->get_address();
		$retries = $this->get_option( 'retries' );
		$valid   = $this->get_option( 'retries_valid' );
        $a = $this->checkKey($retries, $ip);
        $b = $this->checkKey($retries, $this->getHash($ip));
        $c = $this->checkKey($valid, $ip);
        $d = $this->checkKey($valid, $this->getHash($ip));

		/* Should we show retries remaining? */
		if ( ! is_array( $retries ) || ! is_array( $valid ) ) {
			/* no retries at all */
			return '';
		}
		if (
            (! isset( $retries[ $ip ] ) && ! isset( $retries[ $this->getHash($ip) ] )) ||
            (! isset( $valid[ $ip ] ) && ! isset( $valid[ $this->getHash($ip) ] )) ||
            ( time() > $c && time() > $d )
        ) {
			/* no: no valid retries */
			return '';
		}
		if (
            ( $a % $this->get_option( 'allowed_retries' ) ) == 0 &&
            ( $b % $this->get_option( 'allowed_retries' ) ) == 0
        ) {
			/* no: already been locked out for these retries */
			return '';
		}

        $remaining = max( ( $this->get_option( 'allowed_retries' ) - ( ($a + $b) % $this->get_option( 'allowed_retries' ) ) ), 0 );

		return sprintf( _n( "<strong>%d</strong> attempt remaining.", "<strong>%d</strong> attempts remaining.", $remaining, 'limit-login-attempts-reloaded' ), $remaining );
	}

	/**
	 * Get correct remote address
	 *
	 * @return string
	 *
	 */
	public function get_address() {

		$trusted_ip_origins = $this->get_option( 'trusted_ip_origins' );

		if( empty( $trusted_ip_origins ) || !is_array( $trusted_ip_origins ) ) {

			$trusted_ip_origins = array();
		}

		if( !in_array( 'REMOTE_ADDR', $trusted_ip_origins ) ) {

			$trusted_ip_origins[] = 'REMOTE_ADDR';
		}

		$ip = '';
		foreach ( $trusted_ip_origins as $origin ) {

			if( isset( $_SERVER[$origin] ) && !empty( $_SERVER[$origin] ) ) {

				if( strpos( $_SERVER[$origin], ',' ) !== false ) {

					$origin_ips = explode( ',', $_SERVER[$origin] );
					$origin_ips = array_map( 'trim', $origin_ips );

			        if( $origin_ips ) {

						foreach ($origin_ips as $check_ip) {

						    if( $this->is_ip_valid( $check_ip ) ) {

						        $ip = $check_ip;
						        break 2;
                            }
			            }
                    }
                }

                if( $this->is_ip_valid( $_SERVER[$origin] ) ) {

					$ip = $_SERVER[$origin];
					break;
                }
			}
		}

		$ip = preg_replace('/^(\d+\.\d+\.\d+\.\d+):\d+$/', '\1', $ip);

		return $ip;
	}

	/**
	 * @param $ip
	 * @return bool|mixed
	 */
	public function is_ip_valid( $ip ) {

	    if( empty( $ip ) ) return false;

	    return filter_var($ip, FILTER_VALIDATE_IP);
    }

	/**
	* Clean up old lockouts and retries, and save supplied arrays
	*
	* @param null $retries
	* @param null $lockouts
	* @param null $valid
	*/
	public function cleanup( $retries = null, $lockouts = null, $valid = null ) {
		$now      = time();
		$lockouts = ! is_null( $lockouts ) ? $lockouts : $this->get_option( 'lockouts' );

		$log = $this->get_option( 'logged' );

		/* remove old lockouts */
		if ( is_array( $lockouts ) ) {
			foreach ( $lockouts as $ip => $lockout ) {
				if ( $lockout < $now ) {
					unset( $lockouts[ $ip ] );

					if( is_array( $log ) && isset( $log[ $ip ] ) ) {
						foreach ( $log[ $ip ] as $user_login => &$data ) {

							$data['unlocked'] = true;
						}
					}
				}
			}
			$this->update_option( 'lockouts', $lockouts );
		}

		$this->update_option( 'logged', $log );

		/* remove retries that are no longer valid */
		$valid   = ! is_null( $valid ) ? $valid : $this->get_option( 'retries_valid' );
		$retries = ! is_null( $retries ) ? $retries : $this->get_option( 'retries' );
		if ( ! is_array( $valid ) || ! is_array( $retries ) ) {
			return;
		}

		foreach ( $valid as $ip => $lockout ) {
			if ( $lockout < $now ) {
				unset( $valid[ $ip ] );
				unset( $retries[ $ip ] );
			}
		}

		/* go through retries directly, if for some reason they've gone out of sync */
		foreach ( $retries as $ip => $retry ) {
			if ( ! isset( $valid[ $ip ] ) ) {
				unset( $retries[ $ip ] );
			}
		}

		$retries_stats = $this->get_option( 'retries_stats' );

		if($retries_stats) {

			foreach( $retries_stats as $date => $count ) {

				if( strtotime( $date ) < strtotime( '-7 day' ) ) {
					unset($retries_stats[$date]);
				}
			}

			$this->update_option( 'retries_stats', $retries_stats );
        }

		$this->update_option( 'retries', $retries );
		$this->update_option( 'retries_valid', $valid );
	}

	/**
	* Render admin options page
	*/
	public function options_page() {

		$this->use_local_options = !is_network_admin();
		$this->cleanup();

		if( !empty( $_POST ) ) {

			check_admin_referer( 'limit-login-attempts-options' );

            if ( is_network_admin() )
                $this->update_option( 'allow_local_options', !empty($_POST['allow_local_options']) );

            elseif ( $this->network_mode )
                $this->update_option( 'use_local_options', empty($_POST['use_global_options']) );

            /* Should we clear log? */
            if( isset( $_POST[ 'clear_log' ] ) )
            {
                $this->update_option( 'logged', '' );
                $this->show_error( __( 'Cleared IP log', 'limit-login-attempts-reloaded' ) );
            }

            /* Should we reset counter? */
            if( isset( $_POST[ 'reset_total' ] ) )
            {
                $this->update_option( 'lockouts_total', 0 );
                $this->show_error( __( 'Reset lockout count', 'limit-login-attempts-reloaded' ) );
            }

            /* Should we restore current lockouts? */
            if( isset( $_POST[ 'reset_current' ] ) )
            {
                $this->update_option( 'lockouts', array() );
                $this->show_error( __( 'Cleared current lockouts', 'limit-login-attempts-reloaded' ) );
            }

            /* Should we update options? */
            if( isset( $_POST[ 'llar_update_dashboard' ] ) ) {

                $white_list_ips = ( !empty( $_POST['lla_whitelist_ips'] ) ) ? explode("\n", str_replace("\r", "", stripslashes($_POST['lla_whitelist_ips']) ) ) : array();

                if( !empty( $white_list_ips ) ) {
                    foreach( $white_list_ips as $key => $ip ) {
                        if( '' == $ip ) {
                            unset( $white_list_ips[ $key ] );
                        }
                    }
                }
                $this->update_option('whitelist', $white_list_ips );

                $white_list_usernames = ( !empty( $_POST['lla_whitelist_usernames'] ) ) ? explode("\n", str_replace("\r", "", stripslashes($_POST['lla_whitelist_usernames']) ) ) : array();

                if( !empty( $white_list_usernames ) ) {
                    foreach( $white_list_usernames as $key => $ip ) {
                        if( '' == $ip ) {
                            unset( $white_list_usernames[ $key ] );
                        }
                    }
                }
                $this->update_option('whitelist_usernames', $white_list_usernames );

                $black_list_ips = ( !empty( $_POST['lla_blacklist_ips'] ) ) ? explode("\n", str_replace("\r", "", stripslashes($_POST['lla_blacklist_ips']) ) ) : array();

                if( !empty( $black_list_ips ) ) {
                    foreach( $black_list_ips as $key => $ip ) {
                        $range = array_map('trim', explode('-', $ip) );
                        if ( count( $range ) > 1 && (float)sprintf("%u",ip2long($range[0])) > (float)sprintf("%u",ip2long($range[1]))) {
                            $this->show_error( __( 'The "'. $ip .'" IP range is invalid', 'limit-login-attempts-reloaded' ) );
                        }
                        if( '' == $ip ) {
                            unset( $black_list_ips[ $key ] );
                        }
                    }
                }
                $this->update_option('blacklist', $black_list_ips );

                $black_list_usernames = ( !empty( $_POST['lla_blacklist_usernames'] ) ) ? explode("\n", str_replace("\r", "", stripslashes($_POST['lla_blacklist_usernames']) ) ) : array();

                if( !empty( $black_list_usernames ) ) {
                    foreach( $black_list_usernames as $key => $ip ) {
                        if( '' == $ip ) {
                            unset( $black_list_usernames[ $key ] );
                        }
                    }
                }
                $this->update_option('blacklist_usernames', $black_list_usernames );

                $this->sanitize_options();

                $this->show_error( __( 'Settings saved.', 'limit-login-attempts-reloaded' ) );
            }
            elseif( isset( $_POST[ 'llar_update_settings' ] ) ) {

                /* Should we support GDPR */
                if( isset( $_POST[ 'gdpr' ] ) ) {

                    $this->update_option( 'gdpr', 1 );
                } else {

                    $this->update_option( 'gdpr', 0 );
                }

                $this->update_option('show_top_level_menu_item', ( isset( $_POST['show_top_level_menu_item'] ) ? 1 : 0 ) );

                $this->update_option('allowed_retries',    (int)$_POST['allowed_retries'] );
                $this->update_option('lockout_duration',   (int)$_POST['lockout_duration'] * 60 );
                $this->update_option('valid_duration',     (int)$_POST['valid_duration'] * 3600 );
                $this->update_option('allowed_lockouts',   (int)$_POST['allowed_lockouts'] );
                $this->update_option('long_duration',      (int)$_POST['long_duration'] * 3600 );
                $this->update_option('notify_email_after', (int)$_POST['email_after'] );
                $this->update_option('active_app',       sanitize_text_field( $_POST['active_app'] ) );
                $this->update_option('gdpr_message',       sanitize_textarea_field( LLA_Helpers::deslash( $_POST['gdpr_message'] ) ) );

                $this->update_option('admin_notify_email', sanitize_email( $_POST['admin_notify_email'] ) );

                $trusted_ip_origins = ( !empty( $_POST['lla_trusted_ip_origins'] ) )
                    ? array_map( 'trim', explode( ',', sanitize_text_field( $_POST['lla_trusted_ip_origins'] ) ) )
                    : array();

                if( !in_array( 'REMOTE_ADDR', $trusted_ip_origins ) ) {

                    $trusted_ip_origins[] = 'REMOTE_ADDR';
                }

                $this->update_option('trusted_ip_origins', $trusted_ip_origins );

                $notify_methods = array();

                if( isset( $_POST[ 'lockout_notify_email' ] ) ) {
                    $notify_methods[] = 'email';
                }
                $this->update_option('lockout_notify', implode( ',', $notify_methods ) );

                $this->sanitize_options();

                if( !empty( $_POST['llar_app_settings'] ) && $this->app ) {

                    if( ( $app_setup_code = $this->get_option( 'app_setup_code' ) ) && $setup_result = LLAR_App::setup( strrev( $app_setup_code ) ) ) {

                        if( $setup_result['success'] && $active_app_config = $setup_result['app_config'] ) {

							foreach ( $_POST['llar_app_settings'] as $key => $value ) {

								if( array_key_exists( $key, $active_app_config['settings'] ) ) {

									if( !empty( $active_app_config['settings'][$key]['options'] ) &&
										!in_array( $value, $active_app_config['settings'][$key]['options'] ) ) {

										continue;
									}

									$active_app_config['settings'][$key]['value'] = $value;
								}
							}

							$this->update_option( 'app_config', $active_app_config );
                        }
                    }
                }

                $this->show_error( __( 'Settings saved.', 'limit-login-attempts-reloaded' ) );
            }
		}

		include_once( LLA_PLUGIN_DIR . '/views/options-page.php' );
	}

	/**
	 * Render Welcome page
	 */
	public function welcome_page() {

		include_once( LLA_PLUGIN_DIR . '/views/welcome-page.php' );
    }

	public function ajax_unlock()
	{
		check_ajax_referer('limit-login-unlock', 'sec');
		$ip = (string)@$_POST['ip'];

		$lockouts = (array)$this->get_option('lockouts');

		if ( isset( $lockouts[ $ip ] ) )
		{
			unset( $lockouts[ $ip ] );
			$this->update_option( 'lockouts', $lockouts );
		}

		//save to log
		$user_login = @(string)$_POST['username'];
		$log = $this->get_option( 'logged' );

		if ( @$log[ $ip ][ $user_login ] )
		{
			if ( !is_array( $log[ $ip ][ $user_login ] ) )
			$log[ $ip ][ $user_login ] = array(
				'counter' => $log[ $ip ][ $user_login ],
			);
			$log[ $ip ][ $user_login ]['unlocked'] = true;

			$this->update_option( 'logged', $log );
		}

		header('Content-Type: application/json');
		echo 'true';
		exit;
	}

	/**
	* Show error message
	*
	* @param $msg
	*/
	public function show_error( $msg ) {
		LLA_Helpers::show_error( $msg );
	}

    /**
     * returns IP with its md5 value
     */
    private function getHash($str)
    {
        return md5($str);
    }

    /**
     * @param $arr - array
     * @param $k - key
     * @return int array value at given index or zero
     */
    private function checkKey($arr, $k)
    {
        return isset($arr[$k]) ? $arr[$k] : 0;
    }

	public function show_leave_review_notice() {

		$screen = get_current_screen();

		if(isset($_COOKIE['llar_review_notice_shown'])) {

			$this->update_option('review_notice_shown', true);
			@setcookie('llar_review_notice_shown', '', time() - 3600, '/');
		}

        if ( !current_user_can('manage_options') ||
            $this->get_option('review_notice_shown') ||
            !in_array( $screen->base, array( 'dashboard', 'plugins', 'toplevel_page_limit-login-attempts' ) ) ) return;

        $activation_timestamp = $this->get_option('activation_timestamp');

		if ( $activation_timestamp && $activation_timestamp < strtotime("-1 month") ) { ?>

			<div id="message" class="updated fade notice is-dismissible llar-notice-review">
                <div class="llar-review-image">
                    <img width="80px" src="<?php echo LLA_PLUGIN_URL?>assets/img/icon-256x256.png" alt="review-logo">
                </div>
				<div class="llar-review-info">
				    <p><?php _e('Hey <strong>Limit Login Attempts Reloaded</strong> user!', 'limit-login-attempts-reloaded'); ?></p>
                    <!--<p><?php _e('A <strong>crazy idea</strong> we wanted to share! What if we put an image from YOU on the <a href="https://wordpress.org/plugins/limit-login-attempts-reloaded/" target="_blank">LLAR page</a>?! (<a href="https://wordpress.org/plugins/hello-dolly/" target="_blank">example</a>) A drawing made by you or your child would cheer people up! Send us your drawing by <a href="mailto:wpchef.me@gmail.com" target="_blank">email</a> and we like it, we\'ll add it in the next release. Let\'s have some fun!', 'limit-login-attempts-reloaded'); ?></p> Also, -->
                    <p><?php _e('We would really like to hear your feedback about the plugin! Please take a couple minutes to write a few words <a href="https://wordpress.org/support/plugin/limit-login-attempts-reloaded/reviews/#new-post" target="_blank">here</a>. Thank you!', 'limit-login-attempts-reloaded'); ?></p>

                    <ul class="llar-buttons">
						<li><a href="#" class="llar-review-dismiss" data-type="dismiss"><?php _e('Don\'t show again', 'limit-login-attempts-reloaded'); ?></a></li>
                        <li><i class=""></i><a href="#" class="llar-review-dismiss button" data-type="later"><?php _e('Maybe later', 'limit-login-attempts-reloaded'); ?></a></li>
						<li><a class="button button-primary" target="_blank" href="https://wordpress.org/support/plugin/limit-login-attempts-reloaded/reviews/#new-post"><?php _e('Leave a review', 'limit-login-attempts-reloaded'); ?></a></li>
                    </ul>
                </div>
			</div>
            <script type="text/javascript">
                (function($){

                    $(document).ready(function(){
                        $('.llar-review-dismiss').on('click', function(e) {
                            e.preventDefault();

                            var type = $(this).data('type');

                            $.post(ajaxurl, {
                                action: 'dismiss_review_notice',
                                type: type,
                                sec: '<?php echo wp_create_nonce( "llar-action" ); ?>'
                            });

                            $(this).closest('.llar-notice-review').remove();
                        });

                        $(".llar-notice-review").on("click", ".notice-dismiss", function (event) {
                            createCookie('llar_review_notice_shown', '1', 30);
                        });

                        function createCookie(name, value, days) {
                            var expires;

                            if (days) {
                                var date = new Date();
                                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                                expires = "; expires=" + date.toGMTString();
                            } else {
                                expires = "";
                            }
                            document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
                        }
                    });

                })(jQuery);
            </script>
			<?php
		}
	}

	public function show_enable_notify_notice() {

		$screen = get_current_screen();

		if(isset($_COOKIE['llar_enable_notify_notice_shown'])) {

			$this->update_option('enable_notify_notice_shown', true);
			@setcookie('llar_enable_notify_notice_shown', '', time() - 3600, '/');
		}

		$active_app = $this->get_option( 'active_app' );
		$notify_methods = explode( ',', $this->get_option( 'lockout_notify' ) );

        if ( $active_app !== 'local' ||
             in_array( 'email', $notify_methods ) ||
             !current_user_can('manage_options') ||
             $this->get_option('enable_notify_notice_shown') ||
             $screen->parent_base === 'edit' ) return;

        $activation_timestamp = $this->get_option('notice_enable_notify_timestamp');

		if ( $activation_timestamp && $activation_timestamp < strtotime("-1 month") ) {

			$review_activation_timestamp = $this->get_option('activation_timestamp');
			if ( $review_activation_timestamp && $review_activation_timestamp < strtotime("-1 month") ) {
				$this->update_option( 'activation_timestamp', time() );
            }

		    ?>

			<div id="message" class="updated fade notice is-dismissible llar-notice-notify">
                <div class="llar-review-image">
                    <span class="dashicons dashicons-warning"></span>
                </div>
				<div class="llar-review-info">
				    <p><?php _e('You have been upgraded to the latest version of <strong>Limit Login Attempts Reloaded</strong>.<br> ' .
                            'Due to increased security threats around the holidays, we recommend turning on email ' .
                            'notifications when you receive a failed login attempt.', 'limit-login-attempts-reloaded'); ?></p>

                    <ul class="llar-buttons">
                        <li><a class="button button-primary llar-ajax-enable-notify" target="_blank" href="#"><?php _e('Yes, turn on email notifications', 'limit-login-attempts-reloaded'); ?></a></li>
                        <li><a href="#" class="llar-notify-notice-dismiss button" data-type="later"><?php _e('Remind me a month from now', 'limit-login-attempts-reloaded'); ?></a></li>
                        <li><a href="#" class="llar-notify-notice-dismiss" data-type="dismiss"><?php _e('Don\'t show this message again', 'limit-login-attempts-reloaded'); ?></a></li>
                    </ul>
                </div>
			</div>
            <script type="text/javascript">
                (function($){

                    $(document).ready(function(){
                        $('.llar-notify-notice-dismiss').on('click', function(e) {
                            e.preventDefault();

                            var type = $(this).data('type');

                            $.post(ajaxurl, {
                                action: 'dismiss_notify_notice',
                                type: type,
                                sec: '<?php echo wp_create_nonce( "llar-action" ); ?>'
                            });

                            $(this).closest('.llar-notice-notify').remove();
                        });

                        $(".llar-notice-notify").on("click", ".notice-dismiss", function (e) {
                            createCookie('llar_enable_notify_notice_shown', '1', 30);
                        });

                        $(".llar-ajax-enable-notify").on("click", function (e) {
							e.preventDefault();

							$.post(ajaxurl, {
								action: 'enable_notify',
								sec: '<?php echo wp_create_nonce( "llar-action" ); ?>'
							}, function(response){

								if(response.success) {
									$(".llar-notice-notify .llar-review-info p").text('You are all set!');
									$(".llar-notice-notify .llar-buttons").remove();
                                }

                            });
                        });

                        function createCookie(name, value, days) {
                            var expires;

                            if (days) {
                                var date = new Date();
                                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                                expires = "; expires=" + date.toGMTString();
                            } else {
                                expires = "";
                            }
                            document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
                        }
                    });

                })(jQuery);
            </script>
			<?php
		}
	}

	public function dismiss_review_notice_callback() {

		if ( !current_user_can('activate_plugins') ) {

		    wp_send_json_error(array());
        }

		check_ajax_referer('llar-action', 'sec');

		$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : false;

		if ($type === 'dismiss'){

			$this->update_option( 'review_notice_shown', true );
		}

		if ($type === 'later') {

			$this->update_option( 'activation_timestamp', time() );
		}

		wp_send_json_success(array());
	}

	public function dismiss_notify_notice_callback() {

		if ( !current_user_can('activate_plugins') ) {

		    wp_send_json_error(array());
        }

		check_ajax_referer('llar-action', 'sec');

		$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : false;

		if ($type === 'dismiss'){

			$this->update_option( 'enable_notify_notice_shown', true );
		}

		if ($type === 'later') {

			$this->update_option( 'notice_enable_notify_timestamp', time() );
		}

		wp_send_json_success(array());
	}

	public function enable_notify_callback() {

		if ( !current_user_can('activate_plugins') ) {

		    wp_send_json_error(array());
        }

		check_ajax_referer('llar-action', 'sec');

		$notify_methods = explode( ',', $this->get_option( 'lockout_notify' ) );

        if( !in_array( 'email', $notify_methods ) ) {

            $notify_methods[] = 'email';
		}

		$this->update_option( 'lockout_notify', implode( ',', $notify_methods ) );
		$this->update_option( 'enable_notify_notice_shown', true );

		wp_send_json_success(array());
	}

	public function app_setup_callback() {

		if ( !current_user_can('activate_plugins') ) {

			wp_send_json_error(array());
		}

		check_ajax_referer('llar-action', 'sec');

		if( !empty( $_POST['code'] ) ) {

			$setup_code = sanitize_text_field( $_POST['code'] );
			$link = strrev( $setup_code );

			if( $setup_result = LLAR_App::setup( $link ) ) {

			    if( $setup_result['success'] ) {

			        if( $setup_result['app_config'] ) {

						$this->app_update_config( $setup_result['app_config'], true );
						$this->update_option( 'active_app', 'custom' );

						$this->update_option( 'app_setup_code', $setup_code );

						wp_send_json_success(array(
							'msg' => ( !empty( $setup_result['app_config']['messages']['setup_success'] ) )
                                        ? $setup_result['app_config']['messages']['setup_success']
                                        : __( 'The app has been successfully imported.', 'limit-login-attempts-reloaded' )
						));
					}

				} else {

					wp_send_json_error(array(
						'msg' => $setup_result['error']
					));
				}
			}
		}

		wp_send_json_error(array(
			'msg' => __( 'Please specify the Setup Code', 'limit-login-attempts-reloaded' )
		));
    }

    public function app_update_config( $new_app_config, $update_created_at = false ) {
        
        if( !$new_app_config ) return false;

		if( $active_app_config = $this->get_custom_app_config() ) {

			foreach ( $active_app_config['settings'] as $key => $info ) {

				if( array_key_exists( $key, $new_app_config['settings'] ) ) {

					if( !empty( $new_app_config['settings'][$key]['options'] ) &&
						!in_array( $info['value'], $new_app_config['settings'][$key]['options'] ) ) {

						continue;
					}

					$new_app_config['settings'][$key]['value'] = $info['value'];
				}
			}

		}

		if( $update_created_at )
		    $new_app_config['created_at'] = time();

		$this->update_option( 'app_config', $new_app_config );
    }

	public function app_log_action_callback() {

		if ( !current_user_can('activate_plugins') ) {

			wp_send_json_error(array());
		}

		check_ajax_referer('llar-action', 'sec');

		if( !empty( $_POST['method'] ) && !empty( $_POST['params'] ) ) {

		    $method = sanitize_text_field( $_POST['method'] );
		    $params = (array) $_POST['params'];

		    if( !in_array( $method, array( 'lockout/delete', 'acl/create', 'acl/delete' ) ) ) {

				wp_send_json_error(array(
					'msg' => 'Wrong method.'
				));
            }

		    if( $response = $this->app->request( $method, 'post', $params ) ) {

				wp_send_json_success(array(
					'msg' => $response['message']
				));

            } else {

				wp_send_json_error(array(
					'msg' => 'The endpoint is not responding. Please contact your app provider to settle that.'
				));
            }
		}

		wp_send_json_error(array(
			'msg' => 'Wrong App id.'
		));
    }

	public function app_acl_add_rule_callback() {

		if ( !current_user_can('activate_plugins') ) {

			wp_send_json_error(array());
		}

		check_ajax_referer('llar-action', 'sec');

		if( !empty( $_POST['pattern'] ) && !empty( $_POST['rule'] ) && !empty( $_POST['type'] ) ) {

		    $pattern = sanitize_text_field( $_POST['pattern'] );
			$rule = sanitize_text_field( $_POST['rule'] );
		    $type = sanitize_text_field( $_POST['type'] );

		    if( !in_array( $rule, array( 'pass', 'allow', 'deny' ) ) ) {

				wp_send_json_error(array(
					'msg' => 'Wrong rule.'
				));
            }

		    if( $response = $this->app->acl_create( array(
                'pattern'   => $pattern,
                'rule'      => $rule,
                'type'      => ( $type === 'ip' ) ? 'ip' : 'login',
            ) ) ) {

				wp_send_json_success(array(
					'msg' => $response['message']
				));

            } else {

				wp_send_json_error(array(
					'msg' => 'The endpoint is not responding. Please contact your app provider to settle that.'
				));
            }
		}

		wp_send_json_error(array(
			'msg' => 'Wrong input data.'
		));
    }

	public function app_acl_remove_rule_callback() {

		if ( !current_user_can('activate_plugins') ) {

			wp_send_json_error(array());
		}

		check_ajax_referer('llar-action', 'sec');

		if( !empty( $_POST['pattern'] ) && !empty( $_POST['type'] ) ) {

		    $pattern = sanitize_text_field( $_POST['pattern'] );
		    $type = sanitize_text_field( $_POST['type'] );

		    if( $response = $this->app->acl_delete( array(
                'pattern'   => $pattern,
                'type'      => ( $type === 'ip' ) ? 'ip' : 'login',
            ) ) ) {

				wp_send_json_success(array(
					'msg' => $response['message']
				));

            } else {

				wp_send_json_error(array(
					'msg' => 'The endpoint is not responding. Please contact your app provider to settle that.'
				));
            }
		}

		wp_send_json_error(array(
			'msg' => 'Wrong input data.'
		));
    }

	public function app_load_log_callback() {

		if ( !current_user_can('activate_plugins') ) {

			wp_send_json_error(array());
		}

		check_ajax_referer('llar-action', 'sec');

		$offset = sanitize_text_field( $_POST['offset'] );
		$limit = sanitize_text_field( $_POST['limit'] );

		$log = $this->app->log( $limit, $offset );

		if( $log ) {

		    ob_start();

			$date_format = get_option('date_format') . ' ' . get_option('time_format');
			?>

			<?php if( $log['items'] ) : ?>

				<?php foreach ( $log['items'] as $item ) : ?>
                    <tr>
                        <td class="llar-col-nowrap"><?php echo get_date_from_gmt( date( 'Y-m-d H:i:s', $item['created_at'] ), $date_format ); ?></td>
                        <td><?php echo esc_html( $item['ip'] ); ?></td>
                        <td><?php echo esc_html( $item['gateway'] ); ?></td>
                        <td><?php echo (is_null($item['login'])) ? '-' : esc_html( $item['login'] ); ?></td>
                        <td><?php echo (is_null($item['result'])) ? '-' : esc_html( $item['result'] ); ?></td>
                        <td><?php echo (is_null($item['reason'])) ? '-' : esc_html( $item['reason'] ); ?></td>
                        <td><?php echo (is_null($item['pattern'])) ? '-' : esc_html( $item['pattern'] ); ?></td>
                        <td><?php echo (is_null($item['attempts_left'])) ? '-' : esc_html( $item['attempts_left'] ); ?></td>
                        <td><?php echo (is_null($item['time_left'])) ? '-' : esc_html( $item['time_left'] ) ?></td>
                        <td class="llar-app-log-actions">
							<?php
							if( $item['actions'] ) {

								foreach ( $item['actions'] as $action ) {

									echo '<button class="button llar-app-log-action-btn js-app-log-action" style="color:' . esc_attr( $action['color'] ) . ';border-color:' . esc_attr( $action['color'] ) . '" 
                                    data-method="' . esc_attr( $action['method'] ) . '" 
                                    data-params="' . esc_attr( json_encode( $action['data'], JSON_FORCE_OBJECT ) ) . '" 
                                    href="#" title="' . $action['label'] . '"><i class="dashicons dashicons-' . esc_attr( $action['icon']  ) . '"></i></button>';
								}
							} else {
								echo '-';
							}
							?>
                        </td>
                    </tr>
				<?php endforeach; ?>
			<?php else : ?>
                <?php if( empty( $offset ) ) : ?>
                    <tr class="empty-row"><td colspan="100%" style="text-align: center"><?php _e('No events yet.', 'limit-login-attempts-reloaded' ); ?></td></tr>
                <?php endif; ?>
			<?php endif; ?>
<?php

			wp_send_json_success(array(
				'html' => ob_get_clean(),
                'offset' => $log['offset'],
                'total_items' => count( $log['items'] )
			));

        } else {

			wp_send_json_error(array(
				'msg' => 'The endpoint is not responding. Please contact your app provider to settle that.'
			));
        }
    }

	public function app_load_lockouts_callback() {

		if ( !current_user_can('activate_plugins') ) {

			wp_send_json_error(array());
		}

		check_ajax_referer('llar-action', 'sec');

		$offset = sanitize_text_field( $_POST['offset'] );
		$limit = sanitize_text_field( $_POST['limit'] );

		$lockouts = $this->app->get_lockouts( $limit, $offset );

		if( $lockouts ) {

		    ob_start(); ?>

			<?php if( $lockouts['items'] ) : ?>
				<?php foreach ( $lockouts['items'] as $item ) : ?>
                    <tr>
                        <td><?php echo esc_html( $item['ip'] ); ?></td>
                        <td><?php echo (is_null($item['login'])) ? '-' : esc_html( implode( ',', $item['login'] ) ); ?></td>
                        <td><?php echo (is_null($item['count'])) ? '-' : esc_html( $item['count'] ); ?></td>
                        <td><?php echo (is_null($item['ttl'])) ? '-' : esc_html( round( ( $item['ttl'] - time() )  / 60 ) ); ?></td>
                    </tr>
				<?php endforeach; ?>

			<?php else: ?>
                <?php if( empty( $offset ) ) : ?>
                <tr class="empty-row"><td colspan="4" style="text-align: center"><?php _e('No lockouts yet.', 'limit-login-attempts-reloaded' ); ?></td></tr>
			    <?php endif; ?>
			<?php endif; ?>
<?php

			wp_send_json_success(array(
				'html' => ob_get_clean(),
                'offset' => $lockouts['offset']
			));

        } elseif( intval( $this->app->last_response_code ) >= 400 && intval( $this->app->last_response_code ) < 500) {

		    $app_config = $this->get_custom_app_config();

		    wp_send_json_error(array(
				'error_notice' => '<div class="llar-app-notice">
                                        <p>'. $app_config['messages']['sync_error'] .'<br><br>'. sprintf( __( 'Meanwhile, the app falls over to the <a href="%s">default functionality</a>.', 'limit-login-attempts-reloaded' ), admin_url('options-general.php?page=limit-login-attempts&tab=logs-local') ) . '</p>
                                    </div>'
            ));
        } else {

			wp_send_json_error(array(
				'msg' => 'The endpoint is not responding. Please contact your app provider to settle that.'
			));
        }
    }

	public function app_load_acl_rules_callback() {

		if ( !current_user_can('activate_plugins') ) {

			wp_send_json_error(array());
		}

		check_ajax_referer('llar-action', 'sec');

		$type = sanitize_text_field( $_POST['type'] );
		$limit = sanitize_text_field( $_POST['limit'] );
		$offset = sanitize_text_field( $_POST['offset'] );

        $acl_list = $this->app->acl( array(
            'type' => $type,
            'limit' => $limit,
            'offset' => $offset
        ) );

		if( $acl_list ) {

		    ob_start(); ?>

			<?php if( $acl_list['items'] ) : ?>
				<?php foreach ( $acl_list['items'] as $item ) : ?>
                    <tr class="llar-app-rule-<?php echo esc_attr( $item['rule'] ); ?>">
                        <td class="rule-pattern" scope="col"><?php echo esc_html( $item['pattern'] ); ?></td>
                        <td scope="col"><?php echo esc_html( $item['rule'] ); ?><?php echo ($type === 'ip') ? '<span class="origin">'.esc_html( $item['origin'] ).'</span>' : ''; ?></td>
                        <td class="llar-app-acl-action-col" scope="col"><button class="button llar-app-acl-remove" data-type="<?php echo esc_attr( $type ); ?>" data-pattern="<?php echo esc_attr( $item['pattern'] ); ?>"><span class="dashicons dashicons-no"></span></button></td>
                    </tr>
				<?php endforeach; ?>
			<?php else : ?>
                <tr class="empty-row"><td colspan="3" style="text-align: center"><?php _e('No rules yet.', 'limit-login-attempts-reloaded' ); ?></td></tr>
			<?php endif; ?>
<?php

			wp_send_json_success(array(
				'html' => ob_get_clean(),
				'offset' => $acl_list['offset']
			));

        } else {

			wp_send_json_error(array(
				'msg' => 'The endpoint is not responding. Please contact your app provider to settle that.'
			));
        }
    }

    public function app_load_country_access_rules_callback() {

		if ( !current_user_can('activate_plugins') ) {

			wp_send_json_error(array());
		}

		check_ajax_referer('llar-action', 'sec');

		$country_rules = $this->app->country();

		if( $country_rules ) {

			wp_send_json_success($country_rules);
		} else {

			wp_send_json_error(array(
				'msg' => 'Something wrong.'
			));
		}
	}

    public function app_toggle_country_callback() {

		if ( !current_user_can('activate_plugins') ) {

			wp_send_json_error(array());
		}

		check_ajax_referer('llar-action', 'sec');

		$code = sanitize_text_field( $_POST['code'] );
		$action_type = sanitize_text_field( $_POST['type'] );

		if( !$code ) {

			wp_send_json_error(array(
                'msg' => 'Wrong country code.'
            ));
		}

		$result = false;

		if( $action_type === 'add' ) {

			$result = $this->app->country_add(array(
				'code' => $code
			));

		} else if ( $action_type === 'remove' ) {

			$result = $this->app->country_remove(array(
				'code' => $code
			));
		}

		if( $result ) {

		    wp_send_json_success(array());
		} else {

			wp_send_json_error(array(
				'msg' => 'Something wrong.'
			));
		}
	}

    public function app_country_rule_callback() {

		if ( !current_user_can('activate_plugins') ) {

			wp_send_json_error(array());
		}

		check_ajax_referer('llar-action', 'sec');

		$rule = sanitize_text_field( $_POST['rule'] );

		if( empty( $rule ) || !in_array( $rule, array( 'allow', 'deny' ) ) ) {

		    wp_send_json_error(array(
                'msg' => 'Wrong rule.'
            ));
		}

        $result = $this->app->country_rule(array(
            'rule' => $rule
        ));

		if( $result ) {

		    wp_send_json_success(array());
		} else {

			wp_send_json_error(array(
				'msg' => 'Something wrong.'
			));
		}
	}

    public function get_custom_app_config() {

        return $this->get_option( 'app_config' );
	}
}