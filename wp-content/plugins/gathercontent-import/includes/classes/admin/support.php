<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer\Admin;

/**
 * GatherContent Plugin Support
 *
 * @since 3.0.1
 */
class Support extends Base {

	public $menu_priority = 100;

	/**
	 * Constructor. Sets the asset_suffix var.
	 *
	 * @since 3.0.1
	 */
	public function __construct() {
		if (
			isset( $_POST['gc-download-sysinfo-nonce'], $_POST['gc-sysinfo'] )
			&& wp_verify_nonce( $this->_post_val( 'gc-download-sysinfo-nonce' ), 'gc-download-sysinfo-nonce' )
			&& \GatherContent\Importer\user_allowed()
		) {
			$this->download_sys_info();
		}
	}

	/**
	 * Downloads the current system info in a text file.
	 *
	 * @since  3.0.1
	 *
	 * @return void
	 */
	public function download_sys_info() {
		if ( \GatherContent\Importer\user_allowed() ) {
			nocache_headers();

			header( 'Content-Type: text/plain' );
			header( 'Content-Disposition: attachment; filename="gathercontent-system-info.txt"' );

			echo wp_strip_all_tags( $this->_post_val( 'gc-sysinfo' ) );
			die();
		}
	}

	/**
	 * Method needs to be empty.
	 *
	 * @since  3.0.1
	 *
	 * @return void
	 */
	public function initialize_settings_sections() {}

	/**
	 * Registers our menu item and admin page.
	 *
	 * @since  3.0.1
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_submenu_page(
			self::SLUG,
			__( 'Support', 'gathercontent-import' ),
			__( 'Support', 'gathercontent-import' ),
			\GatherContent\Importer\view_capability(),
			self::SLUG . '-support',
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Outputs the system info page.
	 *
	 * @todo   Include ajax tests and GC connectivity tests.
	 * @todo   Output versions/locations of the JS libs we are using.
	 *
	 * @since  3.0.1
	 *
	 * @return void
	 */
	public function admin_page() {
		if ( ! class_exists( 'Browser' ) ) {
			require_once GATHERCONTENT_INC . 'vendor/edd/browser.php';
		}
		$browser = new \Browser();

		global $wpdb;
		// $sys_info = get_transient( 'gc_sys_info' );

		// if ( ! $sys_info || $this->_get_val( 'flush_cache' ) ) {
			$sys_info = $this->view( 'system-info', array(
				'multisite'               => is_multisite() ? 'Yes' : 'No',

				'site_url'                => site_url(),
				'home_url'                => home_url(),

				'gc_version'              => GATHERCONTENT_VERSION,
				'wp_version'              => get_bloginfo( 'version' ),
				'permalink_structure'     => get_option( 'permalink_structure' ),
				'theme'                   => $this->theme(),
				'host'                    => $this->host(),
				'browser'                 => $browser,

				'php_version'             => PHP_VERSION,
				'mysql_version'           => @$wpdb->db_version(),
				'web_server_info'         => $_SERVER['SERVER_SOFTWARE'],

				'wordpress_memory_limit'  => WP_MEMORY_LIMIT,
				'php_safe_mode'           => ini_get( 'safe_mode' ) ? 'Yes' : 'No',
				'php_memory_limit'        => ini_get( 'memory_limit' ),
				'php_upload_max_size'     => ini_get( 'upload_max_filesize' ),
				'php_post_max_size'       => ini_get( 'post_max_size' ),
				'php_upload_max_filesize' => ini_get( 'upload_max_filesize' ),
				'php_time_limit'          => ini_get( 'max_execution_time' ),
				'php_max_input_vars'      => ini_get( 'max_input_vars' ),
				'php_arg_separator'       => ini_get( 'arg_separator.output' ),
				'php_allow_url_file_open' => ini_get( 'allow_url_fopen' ) ? 'Yes' : 'No',

				'debug'                   => defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set',
				'script_debug'            => defined( 'SCRIPT_DEBUG' ) ? SCRIPT_DEBUG ? 'Enabled' : 'Disabled' : 'Not set',

				'pre_length'              => $this->pre_length(),

				'show_on_front'           => get_option( 'show_on_front' ),
				'page_on_front'           => $this->get_page( 'page_on_front' ),
				'page_for_posts'          => $this->get_page( 'page_for_posts' ),

				'wp_remote_post'          => $this->wp_remote_post(),

				'session'                 => isset( $_SESSION ) ? 'Enabled' : 'Disabled',
				'session'                 => esc_html( ini_get( 'session.name' ) ),
				'session_name'            => esc_html( ini_get( 'session.name' ) ),
				'cookie_path'             => esc_html( ini_get( 'session.cookie_path' ) ),
				'save_path'               => esc_html( ini_get( 'session.save_path' ) ),
				'use_cookies'             => ini_get( 'session.use_cookies' ) ? 'On' : 'Off',
				'use_only_cookies'        => ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off',
				'display_errors'          => ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A',
				'fsockopen'               => function_exists( 'fsockopen' ) ? 'Your server supports fsockopen.' : 'Your server does not support fsockopen.',
				'curl'                    => function_exists( 'curl_init' ) ? 'Your server supports cURL.' : 'Your server does not support cURL.',
				'soap_client'             => class_exists( 'SoapClient' ) ? 'Your server has the SOAP Client enabled.' : 'Your server does not have the SOAP Client enabled.',
				'suhosin'                 => extension_loaded( 'suhosin' ) ? 'Your server has SUHOSIN installed.' : 'Your server does not have SUHOSIN installed.',


				'active_plugins'          => $this->active_plugins(),
				'network_active_plugins'  => $this->network_active_plugins(),
				'gc_options'              => print_r( get_option( 'gathercontent_importer' ), 1 ),
			), false );

			// Store for a bit.
		// 	set_transient( 'gc_sys_info', $sys_info, 20 * MINUTE_IN_SECONDS );
		// }

		echo $sys_info;
	}

	public function theme() {
		if ( get_bloginfo( 'version' ) < '3.4' ) {
			$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
			$theme      = $theme_data['Name'] . ' ' . $theme_data['Version'];
		} else {
			$theme_data = wp_get_theme();
			$theme      = $theme_data->Name . ' ' . $theme_data->Version;
		}

		return $theme;
	}

	public function host() {
		// Try to identifty the hosting provider
		$host = false;
		if ( defined( 'WPE_APIKEY' ) ) {
			$host = 'WP Engine';
		} elseif ( defined( 'PAGELYBIN' ) ) {
			$host = 'Pagely';
		}

		return $host;
	}

	public function pre_length() {
		$length = strlen( $GLOBALS['wpdb']->prefix );
		return 'Length: '. $length .', Status: ' . ( $length > 16 ? 'ERROR: Too Long' : 'Acceptable' );
	}

	public function wp_remote_post() {
		$request['cmd'] = '_notify-validate';
		$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', array(
			'sslverify'		=> false,
			'timeout'		=> 60,
			'body'			=> $request
		) );

		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			$works =  'wp_remote_post() works' . "\n";
		} else {
			$works =  'wp_remote_post() does not work' . "\n";
		}

		return $works;
	}

	public function active_plugins() {
		$plugins = '';
		$all_plugins = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );

		foreach ( $all_plugins as $plugin_path => $plugin ) {
			// If the plugin isn't active, don't show it.
			if ( ! in_array( $plugin_path, $active_plugins ) ) {
				continue;
			}

			$plugins .= $plugin['Name'] . ': ' . $plugin['Version'] ."\n";
		}

		return $plugins;
	}

	public function network_active_plugins() {
		if ( ! is_multisite() ) {
			return false;
		}

		$plugins = '';
		$all_plugins = wp_get_active_network_plugins();
		$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

		foreach ( $all_plugins as $plugin_path ) {
			$plugin_base = plugin_basename( $plugin_path );

			// If the plugin isn't active, don't show it.
			if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
				continue;
			}

			$plugin = get_plugin_data( $plugin_path );

			$plugins .= $plugin['Name'] . ': ' . $plugin['Version'] ."\n";
		}

		return $plugins;

	}

	public function get_page( $option_key ) {
		$id = get_option( $option_key );
		$title = get_the_title( $id );
		return ( $title ? $title : 'N/A' ) . ' (#' . $id . ')';
	}

}
