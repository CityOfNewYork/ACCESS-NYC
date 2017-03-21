<?php

/**
 * Class Limit_Login_Attempts
 */
class Limit_Login_Attempts {

	/**
	 * Main plugin options
	 * @var array
	 */
	public $_options = array(
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
		'valid_duration'     => 43200, // 12 hours

		/* Also limit malformed/forged cookies? */
		'cookies'            => true,

		/* Notify on lockout. Values: '', 'log', 'email', 'log,email' */
		'lockout_notify'     => 'log',

		/* If notify by email, do so after this number of lockouts */
		'notify_email_after' => 4,

		'whitelist'          => array()
	);

	/**
	 * Admin options page slug
	 * @var string
	 */
	private $_options_page_slug = 'limit-login-attempts';

	/**
	 * Errors messages
	 *
	 * @var array
	 */
	public $_errors = array();

	public function __construct() {
		$this->hooks_init();
	}

	/**
	 * Register wp hooks and filters
	 */
	public function hooks_init() {
		add_action( 'plugins_loaded', array( $this, 'setup' ), 9999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'limit_login_whitelist_ip', array( $this, 'check_whitelist' ), 10, 2);
	}

	public function check_whitelist( $allow, $ip ) {
		return in_array( $ip, (array) $this->get_option( 'whitelist' ) );
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

		if ( ! $this->is_limit_login_ok() ) {
			return new IXR_Error( 403, $this->error_msg() );
		}

		$ip      = $this->get_address();
		$retries = get_option( 'limit_login_retries' );
		$valid   = get_option( 'limit_login_retries_valid' );

		/* Should we show retries remaining? */

		if ( ! is_array( $retries ) || ! is_array( $valid ) ) {
			/* no retries at all */
			return $error;
		}
		if ( ! isset( $retries[ $ip ] ) || ! isset( $valid[ $ip ] ) || time() > $valid[ $ip ] ) {
			/* no: no valid retries */
			return $error;
		}
		if ( ( $retries[ $ip ] % $this->get_option( 'allowed_retries' ) ) == 0 ) {
			/* no: already been locked out for these retries */
			return $error;
		}

		$remaining = max( ( $this->get_option( 'allowed_retries' ) - ( $retries[ $ip ] % $this->get_option( 'allowed_retries' ) ) ), 0 );

		return new IXR_Error( 403, sprintf( _n( "<strong>%d</strong> attempt remaining.", "<strong>%d</strong> attempts remaining.", $remaining, 'limit-login-attempts-reloaded' ), $remaining ) );
	}

	/**
	 * Errors on WooCommerce account page
	 */
	public function add_wc_notices() {

		global $limit_login_just_lockedout, $limit_login_nonempty_credentials, $limit_login_my_error_shown;

		if ( ! function_exists( 'is_account_page' ) || ! function_exists( 'wc_add_notice' ) ) {
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

	public function setup() {
		$this->check_original_installed();

		load_plugin_textdomain( 'limit-login-attempts-reloaded', false, plugin_basename( dirname( __FILE__ ) ) . '/../languages' );
		$this->setup_options();

		add_action( 'wp_login_failed', array( $this, 'limit_login_failed' ) );

		// TODO: remove this
//        if( $this->get_option( 'cookies' ) ) {
//            $this->handle_cookies();
//
//            add_action( 'auth_cookie_bad_username', array($this, 'failed_cookie') );
//
//            global $wp_version;
//
//            if( version_compare( $wp_version, '3.0', '>=' ) ) {
//                add_action( 'auth_cookie_bad_hash', array($this, 'failed_cookie_hash') );
//                add_action( 'auth_cookie_valid', array($this, 'valid_cookie'), 10, 2 );
//            } else {
//                add_action( 'auth_cookie_bad_hash', array($this, 'failed_cookie') );
//            }
//        }

		add_filter( 'wp_authenticate_user', array( $this, 'wp_authenticate_user' ), 99999, 2 );
		add_filter( 'shake_error_codes', array( $this, 'failure_shake' ) );
		add_action( 'login_head', array( $this, 'add_error_message' ) );
		add_action( 'login_errors', array( $this, 'fixup_error_messages' ) );
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
	}

	/**
	 * Check if the original plugin is installed
	 */
	private function check_original_installed() {

		if ( defined( 'LIMIT_LOGIN_DIRECT_ADDR' ) ) { // Original plugin is installed

			if ( $active_plugins = get_option( 'active_plugins' ) ) {
				$deactivate_this = array(
					'limit-login-attempts-reloaded/limit-login-attempts-reloaded.php'
				);
				$active_plugins  = array_diff( $active_plugins, $deactivate_this );
				update_option( 'active_plugins', $active_plugins );

				add_action( 'admin_notices', function () {
					?>
					<div class="notice notice-error is-dismissible">
						<p><?php _e( 'Please deactivate the Limit Login Attempts first.', 'limit-login-attempts-reloaded' ); ?></p>
					</div>
					<?php
				} );
			}
		}
	}

	/**
	 * Enqueue js and css
	 */
	public function enqueue() {
		wp_enqueue_style( 'lla-main', LLA_PLUGIN_URL . '/assets/css/limit-login-attempts.css' );
	}

	/**
	 * Add admin options page
	 */
	public function admin_menu() {
		global $wp_version;

		// Modern WP?
		if ( version_compare( $wp_version, '3.0', '>=' ) ) {
			add_options_page( 'Limit Login Attempts', 'Limit Login Attempts', 'manage_options', $this->_options_page_slug, array(
				$this,
				'options_page'
			) );

			return;
		}

		// Older WPMU?
		if ( function_exists( "get_current_site" ) ) {
			add_submenu_page( 'wpmu-admin.php', 'Limit Login Attempts', 'Limit Login Attempts', 9, $this->_options_page_slug, array(
				$this,
				'options_page'
			) );

			return;
		}

		// Older WP
		add_options_page( 'Limit Login Attempts', 'Limit Login Attempts', 9, $this->_options_page_slug, array(
			$this,
			'options_page'
		) );
	}

	/**
	 * Get the correct options page URI
	 *
	 * @return mixed
	 */
	public function get_options_page_uri() {
		return menu_page_url( $this->_options_page_slug, false );
	}

	/**
	 * Get option by name
	 *
	 * @param $option_name
	 *
	 * @return null
	 */
	public function get_option( $option_name ) {
		return ( isset( $this->_options[ $option_name ] ) ) ? $this->_options[ $option_name ] : null;
	}

	/**
	 * Setup main options
	 */
	public function setup_options() {
		$this->update_option_from_db( 'limit_login_client_type', 'client_type' );
		$this->update_option_from_db( 'limit_login_allowed_retries', 'allowed_retries' );
		$this->update_option_from_db( 'limit_login_lockout_duration', 'lockout_duration' );
		$this->update_option_from_db( 'limit_login_valid_duration', 'valid_duration' );
		$this->update_option_from_db( 'limit_login_cookies', 'cookies' );
		$this->update_option_from_db( 'limit_login_lockout_notify', 'lockout_notify' );
		$this->update_option_from_db( 'limit_login_allowed_lockouts', 'allowed_lockouts' );
		$this->update_option_from_db( 'limit_login_long_duration', 'long_duration' );
		$this->update_option_from_db( 'limit_login_notify_email_after', 'notify_email_after' );
		$this->update_option_from_db( 'limit_login_whitelist', 'whitelist' );

		$this->sanitize_variables();
	}

	public function sanitize_variables() {

		$this->sanitize_simple_int( 'allowed_retries' );
		$this->sanitize_simple_int( 'lockout_duration' );
		$this->sanitize_simple_int( 'valid_duration' );
		$this->sanitize_simple_int( 'allowed_lockouts' );
		$this->sanitize_simple_int( 'long_duration' );

		$this->_options['cookies'] = ! ! $this->get_option( 'cookies' );

		$notify_email_after                   = max( 1, intval( $this->get_option( 'notify_email_after' ) ) );
		$this->_options['notify_email_after'] = min( $this->get_option( 'allowed_lockouts' ), $notify_email_after );

		$args         = explode( ',', $this->get_option( 'lockout_notify' ) );
		$args_allowed = explode( ',', LLA_LOCKOUT_NOTIFY_ALLOWED );
		$new_args     = array();
		foreach ( $args as $a ) {
			if ( in_array( $a, $args_allowed ) ) {
				$new_args[] = $a;
			}
		}
		$this->_options['lockout_notify'] = implode( ',', $new_args );

		if ( $this->get_option( 'client_type' ) != LLA_DIRECT_ADDR && $this->get_option( 'client_type' ) != LLA_PROXY_ADDR ) {
			$this->_options['client_type'] = LLA_DIRECT_ADDR;
		}

	}

	/**
	 * Make sure the variables make sense -- simple integer
	 *
	 * @param $var_name
	 */
	public function sanitize_simple_int( $var_name ) {
		$this->_options[ $var_name ] = max( 1, intval( $this->get_option( $var_name ) ) );
	}

	/**
	 * Update options in db from global variables
	 */
	public function update_options() {
		update_option( 'limit_login_client_type', $this->get_option( 'client_type' ) );
		update_option( 'limit_login_allowed_retries', $this->get_option( 'allowed_retries' ) );
		update_option( 'limit_login_lockout_duration', $this->get_option( 'lockout_duration' ) );
		update_option( 'limit_login_allowed_lockouts', $this->get_option( 'allowed_lockouts' ) );
		update_option( 'limit_login_long_duration', $this->get_option( 'long_duration' ) );
		update_option( 'limit_login_valid_duration', $this->get_option( 'valid_duration' ) );
		update_option( 'limit_login_lockout_notify', $this->get_option( 'lockout_notify' ) );
		update_option( 'limit_login_notify_email_after', $this->get_option( 'notify_email_after' ) );
		update_option( 'limit_login_cookies', $this->get_option( 'cookies' ) ? '1' : '0' );
		update_option( 'limit_login_whitelist', $this->get_option( 'whitelist' ) );
	}

	public function update_option_from_db( $option, $var_name ) {
		if ( false !== ( $option_value = get_option( $option ) ) ) {
			$this->_options[ $var_name ] = $option_value;
		}
	}

	/**
	 * Action: successful cookie login
	 *
	 * Clear any stored user_meta.
	 *
	 * Requires WordPress version 3.0.0, not used in previous versions
	 *
	 * @param $cookie_elements
	 * @param $user
	 */
	public function valid_cookie( $cookie_elements, $user ) {
		/*
		 * As all meta values get cached on user load this should not require
		 * any extra work for the common case of no stored value.
		 */

		if ( get_user_meta( $user->ID, 'limit_login_previous_cookie' ) ) {
			delete_user_meta( $user->ID, 'limit_login_previous_cookie' );
		}
	}

	/**
	 * Action: failed cookie login (calls limit_login_failed())
	 *
	 * @param $cookie_elements
	 */
	public function failed_cookie( $cookie_elements ) {
		$this->clear_auth_cookie();

		/*
		 * Invalid username gets counted every time.
		 */
		$this->limit_login_failed( $cookie_elements['username'] );
	}

	/**
	 * Action: failed cookie login hash
	 *
	 * Make sure same invalid cookie doesn't get counted more than once.
	 *
	 * Requires WordPress version 3.0.0, previous versions use limit_login_failed_cookie()
	 *
	 * @param $cookie_elements
	 */
	public function failed_cookie_hash( $cookie_elements ) {
		$this->clear_auth_cookie();

		/*
		 * Under some conditions an invalid auth cookie will be used multiple
		 * times, which results in multiple failed attempts from that one
		 * cookie.
		 *
		 * Unfortunately I've not been able to replicate this consistently and
		 * thus have not been able to make sure what the exact cause is.
		 *
		 * Probably it is because a reload of for example the admin dashboard
		 * might result in multiple requests from the browser before the invalid
		 * cookie can be cleard.
		 *
		 * Handle this by only counting the first attempt when the exact same
		 * cookie is attempted for a user.
		 */

		extract( $cookie_elements, EXTR_OVERWRITE );

		// Check if cookie is for a valid user
		$user = get_user_by( 'login', $username );
		if ( ! $user ) {
			// "shouldn't happen" for this action
			$this->limit_login_failed( $username );

			return;
		}

		$previous_cookie = get_user_meta( $user->ID, 'limit_login_previous_cookie', true );
		if ( $previous_cookie && $previous_cookie == $cookie_elements ) {
			// Identical cookies, ignore this attempt
			return;
		}

		// Store cookie
		if ( $previous_cookie ) {
			update_user_meta( $user->ID, 'limit_login_previous_cookie', $cookie_elements );
		} else {
			add_user_meta( $user->ID, 'limit_login_previous_cookie', $cookie_elements, true );
		}

		$this->limit_login_failed( $username );
	}

	/**
	 * Must be called in plugin_loaded (really early) to make sure we do not allow
	 * auth cookies while locked out.
	 */
	public function handle_cookies() {
		if ( $this->is_limit_login_ok() ) {
			return;
		}

		$this->clear_auth_cookie();
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
		$lockouts = get_option( 'limit_login_lockouts' );

		return ( ! is_array( $lockouts ) || ! isset( $lockouts[ $ip ] ) || time() >= $lockouts[ $ip ] );
	}

	/**
	 * Make sure auth cookie really get cleared (for this session too)
	 */
	public function clear_auth_cookie() {

		if ( ! function_exists( 'wp_get_current_user' ) ) {
			include( ABSPATH . "wp-includes/pluggable.php" );
		}

		wp_clear_auth_cookie();

		if ( ! empty( $_COOKIE[ AUTH_COOKIE ] ) ) {
			$_COOKIE[ AUTH_COOKIE ] = '';
		}
		if ( ! empty( $_COOKIE[ SECURE_AUTH_COOKIE ] ) ) {
			$_COOKIE[ SECURE_AUTH_COOKIE ] = '';
		}
		if ( ! empty( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
			$_COOKIE[ LOGGED_IN_COOKIE ] = '';
		}
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

		$ip = $this->get_address();

		/* if currently locked-out, do not add to retries */
		$lockouts = get_option( 'limit_login_lockouts' );
		if ( ! is_array( $lockouts ) ) {
			$lockouts = array();
		}
		if ( isset( $lockouts[ $ip ] ) && time() < $lockouts[ $ip ] ) {
			return;
		}

		/* Get the arrays with retries and retries-valid information */
		$retries = get_option( 'limit_login_retries' );
		$valid   = get_option( 'limit_login_retries_valid' );
		if ( ! is_array( $retries ) ) {
			$retries = array();
			add_option( 'limit_login_retries', $retries, '', 'no' );
		}
		if ( ! is_array( $valid ) ) {
			$valid = array();
			add_option( 'limit_login_retries_valid', $valid, '', 'no' );
		}

		/* Check validity and add one to retries */
		if ( isset( $retries[ $ip ] ) && isset( $valid[ $ip ] ) && time() < $valid[ $ip ] ) {
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
			if ( $retries[ $ip ] >= $retries_long ) {
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
		$total = get_option( 'limit_login_lockouts_total' );
		if ( $total === false || ! is_numeric( $total ) ) {
			add_option( 'limit_login_lockouts_total', 1, '', 'no' );
		} else {
			update_option( 'limit_login_lockouts_total', $total + 1 );
		}
	}

	/**
	 * Handle notification in event of lockout
	 *
	 * @param $user
	 */
	public function notify( $user ) {
		$args = explode( ',', $this->get_option( 'lockout_notify' ) );

		if ( empty( $args ) ) {
			return;
		}

		foreach ( $args as $mode ) {
			switch ( trim( $mode ) ) {
				case 'email':
					$this->notify_email( $user );
					break;
				case 'log':
					$this->notify_log( $user );
					break;
			}
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

		$retries = get_option( 'limit_login_retries' );
		if ( ! is_array( $retries ) ) {
			$retries = array();
		}

		/* check if we are at the right nr to do notification */
		if ( isset( $retries[ $ip ] ) && ( ( $retries[ $ip ] / $this->get_option( 'allowed_retries' ) ) % $this->get_option( 'notify_email_after' ) ) != 0 ) {
			return;
		}

		/* Format message. First current lockout duration */
		if ( ! isset( $retries[ $ip ] ) ) {
			/* longer lockout */
			$count    = $this->get_option( 'allowed_retries' )
			            * $this->get_option( 'allowed_lockouts' );
			$lockouts = $this->get_option( 'allowed_lockouts' );
			$time     = round( $this->get_option( 'long_duration' ) / 3600 );
			$when     = sprintf( _n( '%d hour', '%d hours', $time, 'limit-login-attempts-reloaded' ), $time );
		} else {
			/* normal lockout */
			$count    = $retries[ $ip ];
			$lockouts = floor( $count / $this->get_option( 'allowed_retries' ) );
			$time     = round( $this->get_option( 'lockout_duration' ) / 60 );
			$when     = sprintf( _n( '%d minute', '%d minutes', $time, 'limit-login-attempts-reloaded' ), $time );
		}

		$blogname = $this->is_multisite() ? get_site_option( 'site_name' ) : get_option( 'blogname' );

		if ( $whitelisted ) {
			$subject = sprintf( __( "[%s] Failed login attempts from whitelisted IP"
					, 'limit-login-attempts-reloaded' )
				, $blogname );
		} else {
			$subject = sprintf( __( "[%s] Too many failed login attempts"
					, 'limit-login-attempts-reloaded' )
				, $blogname );
		}

		$message = sprintf( __( "%d failed login attempts (%d lockout(s)) from IP: %s"
				, 'limit-login-attempts-reloaded' ) . "\r\n\r\n"
			, $count, $lockouts, $ip );
		if ( $user != '' ) {
			$message .= sprintf( __( "Last user attempted: %s", 'limit-login-attempts-reloaded' )
			                     . "\r\n\r\n", $user );
		}
		if ( $whitelisted ) {
			$message .= __( "IP was NOT blocked because of external whitelist.", 'limit-login-attempts-reloaded' );
		} else {
			$message .= sprintf( __( "IP was blocked for %s", 'limit-login-attempts-reloaded' ), $when );
		}

		$admin_email = $this->is_multisite() ? get_site_option( 'admin_email' ) : get_option( 'admin_email' );

		@wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Is this WP Multisite?
	 *
	 * @return bool
	 */
	public function is_multisite() {
		return function_exists( 'get_site_option' ) && function_exists( 'is_multisite' ) && is_multisite();
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

		$log = $option = get_option( 'limit_login_logged' );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		$ip = $this->get_address();

		/* can be written much simpler, if you do not mind php warnings */
		if ( isset( $log[ $ip ] ) ) {
			if ( isset( $log[ $ip ][ $user_login ] ) ) {

				if ( is_array( $log[ $ip ][ $user_login ] ) ) { // For new plugin version
					$log[ $ip ][ $user_login ]['counter'] += 1;
				} else { // For old plugin version
					$temp_counter              = $log[ $ip ][ $user_login ];
					$log[ $ip ][ $user_login ] = array(
						'counter' => $temp_counter + 1
					);
				}
			} else {
				$log[ $ip ][ $user_login ] = array(
					'counter' => 1
				);
			}
		} else {
			$log[ $ip ] = array(
				$user_login => array(
					'counter' => 1
				)
			);
		}

		$log[ $ip ][ $user_login ]['date'] = time();

		$gateway = '';
		if( isset( $_POST['woocommerce-login-nonce'] ) ) {
			$gateway = 'WooCommerce';
		} elseif( isset( $GLOBALS['wp_xmlrpc_server'] ) && is_object( $GLOBALS['wp_xmlrpc_server'] ) ) {
			$gateway = 'XMLRPC';
		} else {
			$gateway = 'WP Login';
		}

		$log[ $ip ][ $user_login ]['gateway'] = $gateway;

		if ( $option === false ) {
			add_option( 'limit_login_logged', $log, '', 'no' ); /* no autoload */
		} else {
			update_option( 'limit_login_logged', $log );
		}
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

	/**
	 * Filter: allow login attempt? (called from wp_authenticate())
	 *
	 * @param $user
	 * @param $password
	 *
	 * @return \WP_Error
	 */
	public function wp_authenticate_user( $user, $password ) {

		if ( is_wp_error( $user ) || $this->is_limit_login_ok() ) {
			return $user;
		}

		global $limit_login_my_error_shown;
		$limit_login_my_error_shown = true;

		$error = new WP_Error();
		// This error should be the same as in "shake it" filter below
		$error->add( 'too_many_retries', $this->error_msg() );

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
	 * Should we show errors and messages on this page?
	 *
	 * @return bool
	 */
	public function login_show_msg() {
		if ( isset( $_GET['key'] ) ) {
			/* reset password */
			return false;
		}

		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

		return ( $action != 'lostpassword' && $action != 'retrievepassword'
		         && $action != 'resetpass' && $action != 'rp'
		         && $action != 'register' );
	}

	/**
	 * Construct informative error message
	 *
	 * @return string
	 */
	public function error_msg() {
		$ip       = $this->get_address();
		$lockouts = get_option( 'limit_login_lockouts' );

		$msg = __( '<strong>ERROR</strong>: Too many failed login attempts.', 'limit-login-attempts-reloaded' ) . ' ';

		if ( ! is_array( $lockouts ) || ! isset( $lockouts[ $ip ] ) || time() >= $lockouts[ $ip ] ) {
			/* Huh? No timeout active? */
			$msg .= __( 'Please try again later.', 'limit-login-attempts-reloaded' );

			return $msg;
		}

		$when = ceil( ( $lockouts[ $ip ] - time() ) / 60 );
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
		global $error, $limit_login_my_error_shown;

		if ( ! $this->login_show_msg() || $limit_login_my_error_shown ) {
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

		if ( ! $this->login_show_msg() ) {
			return $content;
		}

		/*
		 * During lockout we do not want to show any other error messages (like
		 * unknown user or empty password).
		 */
		if ( ! $this->is_limit_login_ok() && ! $limit_login_just_lockedout ) {
			return $this->error_msg();
		}

		/*
		 * We want to filter the messages 'Invalid username' and
		 * 'Invalid password' as that is an information leak regarding user
		 * account names (prior to WP 2.9?).
		 *
		 * Also, if more than one error message, put an extra <br /> tag between
		 * them.
		 */
		$msgs = explode( "<br />\n", $content );

		if ( strlen( end( $msgs ) ) == 0 ) {
			/* remove last entry empty string */
			array_pop( $msgs );
		}

		$count         = count( $msgs );
		$my_warn_count = $limit_login_my_error_shown ? 1 : 0;

		if ( $limit_login_nonempty_credentials && $count > $my_warn_count ) {
			/* Replace error message, including ours if necessary */
			$content = __( '<strong>ERROR</strong>: Incorrect username or password.', 'limit-login-attempts-reloaded' ) . "<br />\n";

			if ( $limit_login_my_error_shown || $this->get_message() ) {
				$content .= "<br />\n" . $this->get_message() . "<br />\n";
			}

			return $content;
		} elseif ( $count <= 1 ) {
			return $content;
		}

		$new = '';
		while ( $count -- > 0 ) {
			$new .= array_shift( $msgs ) . "<br />\n";
			if ( $count > 0 ) {
				$new .= "<br />\n";
			}
		}

		return $new;
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

	/**
	 * Construct retries remaining message
	 *
	 * @return string
	 */
	public function retries_remaining_msg() {
		$ip      = $this->get_address();
		$retries = get_option( 'limit_login_retries' );
		$valid   = get_option( 'limit_login_retries_valid' );

		/* Should we show retries remaining? */

		if ( ! is_array( $retries ) || ! is_array( $valid ) ) {
			/* no retries at all */
			return '';
		}
		if ( ! isset( $retries[ $ip ] ) || ! isset( $valid[ $ip ] ) || time() > $valid[ $ip ] ) {
			/* no: no valid retries */
			return '';
		}
		if ( ( $retries[ $ip ] % $this->get_option( 'allowed_retries' ) ) == 0 ) {
			/* no: already been locked out for these retries */
			return '';
		}

		$remaining = max( ( $this->get_option( 'allowed_retries' ) - ( $retries[ $ip ] % $this->get_option( 'allowed_retries' ) ) ), 0 );

		return sprintf( _n( "<strong>%d</strong> attempt remaining.", "<strong>%d</strong> attempts remaining.", $remaining, 'limit-login-attempts-reloaded' ), $remaining );
	}

	/**
	 * Get correct remote address
	 *
	 * @param string $type_name
	 *
	 * @return string
	 */
	public function get_address( $type_name = '' ) {

		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			return $_SERVER['REMOTE_ADDR'];
		} else {
			return '';
		}
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
		$lockouts = ! is_null( $lockouts ) ? $lockouts : get_option( 'limit_login_lockouts' );

		/* remove old lockouts */
		if ( is_array( $lockouts ) ) {
			foreach ( $lockouts as $ip => $lockout ) {
				if ( $lockout < $now ) {
					unset( $lockouts[ $ip ] );
				}
			}
			update_option( 'limit_login_lockouts', $lockouts );
		}

		/* remove retries that are no longer valid */
		$valid   = ! is_null( $valid ) ? $valid : get_option( 'limit_login_retries_valid' );
		$retries = ! is_null( $retries ) ? $retries : get_option( 'limit_login_retries' );
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

		update_option( 'limit_login_retries', $retries );
		update_option( 'limit_login_retries_valid', $valid );
	}

	/**
	 * Render admin options page
	 */
	public function options_page() {
		$this->cleanup();
		include_once( LLA_PLUGIN_DIR . '/views/options-page.php' );
	}

	/**
	 * Show error message
	 *
	 * @param $msg
	 */
	public function show_error( $msg ) {
		LLA_Helpers::show_error( $msg );
	}

}