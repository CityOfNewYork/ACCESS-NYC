<?php

namespace WPScan;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Plugin.
 *
 * General WPScan plugin logic.
 *
 * @since 1.0.0
 */
class Plugin {
	// Settings.
	public $OPT_API_TOKEN         = 'wpscan_api_token';
	public $OPT_SCANNING_INTERVAL = 'wpscan_scanning_interval';
	public $OPT_SCANNING_TIME     = 'wpscan_scanning_time';
	public $OPT_IGNORE_ITEMS      = 'wpscan_ignore_items';
	public $OPT_DISABLE_CHECKS    = 'wpscan_disable_security_checks';

	// Account.
	public $OPT_ACCOUNT_STATUS = 'wpscan_account_status';

	// Notifications.
	public $OPT_EMAIL = 'wpscan_mail';
	public $OPT_INTERVAL = 'wpscan_interval';
	public $OPT_IGNORED = 'wpscan_ignored';

	// Report.
	public $OPT_REPORT = 'wpscan_report';
	public $OPT_ERRORS = 'wpscan_errors';

	// Schedule.
	public $WPSCAN_SCHEDULE = 'wpscan_schedule';

	// Run All schedule.
	public $WPSCAN_RUN_ALL = 'wpscan_run_all';

	// Run Security schedule.
	public $WPSCAN_RUN_SECURITY = 'wpscan_events_inline';

	// Dashboard.
	public $WPSCAN_DASHBOARD = 'wpscan_dashboard';

	// Transient.
	public $WPSCAN_TRANSIENT_CRON = 'wpscan_doing_cron';

	// Required minimal role.
	public $WPSCAN_ROLE = 'manage_options';

	// Plugin path.
	public $plugin_dir = '';

	// Plugin URI.
	public $plugin_url = '';

	// Page.
	public $page_hook = 'toplevel_page_wpscan';

	// Report.
	public $report;

	// Action fired when an issue is found
	public $WPSCAN_ISSUE_FOUND = 'wpscan_issue_found';

	/**
	 * Class constructor.
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		$this->plugin_dir = trailingslashit( str_replace( '\\', '/', dirname( WPSCAN_PLUGIN_FILE ) ) );
		$this->plugin_url = site_url( str_replace( str_replace( '\\', '/', ABSPATH ), '', $this->plugin_dir ) );

		// Languages.
		load_plugin_textdomain( 'wpscan', false, $this->plugin_dir . 'languages' );

		// Cache values in memory.
		$this->report                  = get_option( $this->OPT_REPORT, array() );
		$this->ignored_vulnerabilities = get_option( $this->OPT_IGNORED, array() );

		// Actions.
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 65 );
		add_action( $this->WPSCAN_SCHEDULE, array( $this, 'check_now' ) );
		add_action( $this->WPSCAN_RUN_ALL, array( $this, 'check_now' ) );
		add_action( 'in_admin_header', array( $this, 'deactivate_screen' ) );

		if ( defined( 'WPSCAN_API_TOKEN' ) ) {
			add_action( 'admin_init', array( $this, 'api_token_from_constant' ) );
		}

		// Filters.
		add_filter( 'plugin_action_links_' . plugin_basename( WPSCAN_PLUGIN_FILE ), array( $this, 'add_action_links' ) );

		// Micro apps (modules).
		$this->classes['report']                = new Report( $this );
		$this->classes['settings']              = new Settings( $this );
		$this->classes['account']               = new Account( $this );
		$this->classes['notification']          = new Notification( $this );
		$this->classes['summary']               = new Summary( $this );
		$this->classes['ignoreVulnerabilities'] = new ignoreVulnerabilities( $this );
		$this->classes['dashboard']             = new Dashboard( $this );
		$this->classes['sitehealth']            = new SiteHealth( $this );
		$this->classes['checks/system']         = new Checks\System( $this );
	}

	/**
	 * Plugin Loaded
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function loaded() {
		load_plugin_textdomain( 'wpscan', false, $this->plugin_dir . 'languages' );
	}

	/**
	 * Activate actions. Runs when the plugin is activated.
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function activate() {
		$this->delete_doing_cron_transient();
	}

	/**
	 * Deactivate actions
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function deactivate() {
		delete_option( $this->OPT_SCANNING_INTERVAL );

		delete_option( $this->OPT_SCANNING_TIME );

		as_unschedule_all_actions( $this->WPSCAN_SCHEDULE );
	}

	/**
	 * Deactivate screen
	 *
	 * @return void
	 * @since 1.14.0
	 * @access public
	 */
	public function deactivate_screen() {
		global $pagenow;

		if ( 'plugins.php' === $pagenow ) {
			include_once plugin_dir_path( WPSCAN_PLUGIN_FILE ) . 'views/deactivate.php';
		}
	}

	/**
	 * Use the global constant WPSCAN_API_TOKEN if defined.
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 * @example define('WPSCAN_API_TOKEN', 'xxx');
	 *
	 */
	public function api_token_from_constant() {
		if ( get_option( $this->OPT_API_TOKEN ) !== WPSCAN_API_TOKEN ) {
			$sanitize = $this->classes['settings']->sanitize_api_token( WPSCAN_API_TOKEN );

			if ( $sanitize ) {
				update_option( $this->OPT_API_TOKEN, WPSCAN_API_TOKEN );
			} else {
				delete_option( $this->OPT_API_TOKEN );
			}
		}
	}

	/**
	 * Register Admin Scripts
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_enqueue( $hook ) {
		global $pagenow;
		$screen = get_current_screen();

		if ( $hook === $this->page_hook || 'dashboard' === $screen->id ) {
			wp_enqueue_style(
				'wpscan',
				plugins_url( 'assets/css/style.css', WPSCAN_PLUGIN_FILE ),
				array(),
				$this->wpscan_plugin_version()
			);
		}

		if ( $hook === $this->page_hook ) {
			wp_enqueue_script( 'common' );
			wp_enqueue_script( 'wp-lists' );
			wp_enqueue_script( 'postbox' );

			wp_enqueue_script(
				'wpscan',
				plugins_url( 'assets/js/scripts.js', WPSCAN_PLUGIN_FILE ),
				array( 'jquery' ),
				$this->wpscan_plugin_version()
			);
			
			
			wp_enqueue_script(
				'wpscan-download-report',
				plugins_url( 'assets/js/download-report.js', WPSCAN_PLUGIN_FILE ),
				array( 'pdfmake' ),
				$this->wpscan_plugin_version()
			);
			
			
			wp_enqueue_script(
				'pdfmake',
				plugins_url( 'assets/vendor/pdfmake/pdfmake.min.js', WPSCAN_PLUGIN_FILE ),
				array( 'wpscan' ),
				$this->wpscan_plugin_version()
			);
			
			wp_enqueue_script(
				'wpscan-download-report-fonts',
				plugins_url( 'assets/vendor/pdfmake/vfs_fonts.js', WPSCAN_PLUGIN_FILE ),
				array( 'wpscan' ),
				$this->wpscan_plugin_version()
			);

			$localized = array(
				'ajaxurl'               => admin_url( 'admin-ajax.php' ),
				'action_check'          => 'wpscan_check_now',
				'action_security_check' => 'wpscan_security_check_now',
				'action_cron'           => $this->WPSCAN_TRANSIENT_CRON,
				'ajax_nonce'            => wp_create_nonce( 'wpscan' ),
				'doing_cron'            => false !== as_next_scheduled_action( $this->WPSCAN_RUN_ALL ) ? 'YES' : 'NO',
				'doing_security_cron'   => get_option( $this->WPSCAN_RUN_SECURITY ),
				'running'               => esc_html__( 'Running', 'wpscan' ),
				'not_running'           => esc_html__( 'Run', 'wpscan' ),
			);

			wp_localize_script( 'wpscan', 'wpscan', $localized );
		}

		if ( 'plugins.php' === $pagenow ) {
			wp_enqueue_style(
				'wpscan-deactivate',
				plugins_url( 'assets/css/deactivate.css', WPSCAN_PLUGIN_FILE ),
				array(),
				$this->wpscan_plugin_version()
			);

			wp_enqueue_script(
				'wpscan-deactivate',
				plugins_url( 'assets/js/deactivate.js', WPSCAN_PLUGIN_FILE ),
				array( 'jquery' ),
				$this->wpscan_plugin_version()
			);
		}
	}

	/**
	 * Get the latest report
	 *
	 * @return array|bool
	 * @since 1.0.0
	 * @access public
	 */
	public function get_report() {
		if ( ! empty( $this->report ) ) {
			return ( $this->report );
		}

		return get_option( $this->OPT_REPORT, array() );
	}

	/**
	 * Get the latest report
	 *
	 * @return array|bool
	 * @since 1.0.0
	 * @access public
	 */
	public function get_ignored_vulnerabilities() {
		if ( ! empty( $this->ignored_vulnerabilities ) ) {
			return ( $this->ignored_vulnerabilities );
		}

		return get_option( $this->OPT_IGNORED, array() );
	}

	/**
	 * Return the total of vulnerabilities found or -1 if errors
	 *
	 * @return int
	 * @since 1.0.0
	 * @access public
	 */
	public function get_total() {
		$report = get_option( $this->OPT_REPORT );

		if ( empty( $report ) ) {
			return 0;
		}

		$total = 0;

		foreach ( array( 'wordpress', 'themes', 'plugins', 'security-checks' ) as $type ) {
			if ( isset( $report[ $type ] ) ) {
				if ( isset( $report[ $type ]['total'] ) ) {
					unset( $report[ $type ]['total'] );
				}

				foreach ( $report[ $type ] as $slug => $item ) {
					$p = $report[ $type ][ $slug ];

					if ( isset( $p['vulnerabilities'] ) && is_array( $p['vulnerabilities'] ) ) {
						$total += count( $p['vulnerabilities'] );
					}
				}
			}
		}

		return $total;
	}

	/**
	 * Return the total of vulnerabilities found but not ignored
	 *
	 * @return int
	 * @since 1.0.0
	 * @access public
	 */
	public function get_total_not_ignored() {
		$report  = $this->get_report();
		$ignored = get_option( $this->OPT_IGNORED, array() );

		$total = $this->get_total();

		$types = array( 'wordpress', 'plugins', 'themes', 'security-checks' );

		foreach ( $types as $type ) {
			if ( isset( $report[ $type ] ) ) {
				foreach ( $report[ $type ] as $item ) {
					if ( empty( $item['vulnerabilities'] ) ) {
						continue;
					}

					foreach ( $item['vulnerabilities'] as $vuln ) {
						$id = 'security-checks' === $type ? $vuln['id'] : $vuln->id;

						if ( in_array( $id, $ignored, true ) ) {
							-- $total;
						}
					}
				}
			}
		}

		return $total;
	}

	/**
	 * Create a menu on Tools section
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function menu() {
		$total = $this->get_total_not_ignored();
		$count = $total > 0 ? ' <span class="update-plugins">' . $total . '</span>' : null;

		add_menu_page(
			'WPScan',
			'WPScan' . $count,
			$this->WPSCAN_ROLE,
			'wpscan',
			array( $this->classes['report'], 'page' ),
			plugin_dir_url( dirname( __FILE__ ) ) . 'assets/svg/menu-icon.svg',
			null
		);
	}

	/**
	 * Include a shortcut on Plugins Page
	 *
	 * @param array $links - Array of links provided by the filter
	 *
	 * @access public
	 * @return array
	 * @since 1.0.0
	 */
	public function add_action_links( $links ) {
		$links[] = '<a href="' . admin_url( 'admin.php?page=wpscan' ) . '">' . __( 'View' ) . '</a>';

		return $links;
	}

	/**
	 * Get the WPScan plugin version.
	 *
	 * @return string
	 * @since 1.0.0
	 * @access public
	 */
	public function wpscan_plugin_version() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugin_data( $this->plugin_dir . 'wpscan.php' )['Version'];
	}

	/**
	 * Get information from the API
	 *
	 * @return object|int the JSON object or the response code.
	 * @since 1.0.0
	 * @access public
	 */
	public function api_get( $endpoint, $api_token = null ) {
		if ( empty( $api_token ) ) {
			$api_token = get_option( $this->OPT_API_TOKEN );
		}

		// Make sure endpoint starts with a slash.
		if ( substr( $endpoint, 0, 1 ) !== '/' ) {
			$endpoint = '/' . $endpoint;
		}

		$args = array(
			'headers' => array(
				'Authorization' => 'Token token=' . $api_token,
				'user-agent'    => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url() . ' WPScan/' . $this->wpscan_plugin_version(),
			),
		);

		// Hook before the request.
		//do_action( 'wpscan/api/get/before', $endpoint );

		// Start the request.
		$response = wp_remote_get( WPSCAN_API_URL . $endpoint, $args );
		$code     = wp_remote_retrieve_response_code( $response );

		// Hook after the request.
		//do_action( 'wpscan/api/get/after', $endpoint, $response );

		if ( 200 === $code ) {
			return json_decode( wp_remote_retrieve_body( $response ) );
		} else {
			$errors = get_option( $this->OPT_ERRORS, array() );

			switch ( $code ) {
				case 401:
					array_push( $errors, __( 'Your API Token expired', 'wpscan' ) );
					break;
				case 403:
					array_push( $errors, __( 'You have entered an invalid API Token', 'wpscan' ) );
					break;
				case 404:
					// We don't have the plugin/theme, do nothing.
					break;
				case 429:
					array_push( $errors, sprintf( '%s <a href="%s" target="_blank">%s</a>.', __( 'You hit your API limit. To increase your daily API limit please upgrade via your ', 'wpscan' ), WPSCAN_PROFILE_URL, __( 'WPScan profile page', 'wpscan' ) ) );
					break;
				case 500:
					array_push( $errors, sprintf( '%s <a href="%s" target="_blank">%s</a>', __( 'There seems to be a problem with the WPScan API. Status: 500. Check the ', 'wpscan' ), WPSCAN_STATUS_URL, __( 'API Status', 'wpscan' ) ) );
					break;
				case 502:
					array_push( $errors, sprintf( '%s <a href="%s" target="_blank">%s</a>', __( 'There seems to be a problem with the WPScan API. Status: 502. Check the ', 'wpscan' ), WPSCAN_STATUS_URL, __( 'API Status', 'wpscan' ) ) );
					break;
				case '':
					array_push( $errors, sprintf( '%s <a href="%s" target="_blank">%s</a>', __( 'We were unable to connect to the WPScan API. Check the ', 'wpscan' ), WPSCAN_STATUS_URL, __( 'API Status', 'wpscan' ) ) );
					break;
				default:
					array_push( $errors, sprintf( '%s <a href="%s" target="_blank">%s</a>.', __( 'We received an unknown response from the API. Status: ' . esc_html( $code ), 'wpscan' ), WPSCAN_STATUS_URL, __( 'Check API Status', 'wpscan' ) ) );
					break;
			}

			// Save the errors.
			update_option( $this->OPT_ERRORS, array_unique( $errors ) );
		}

		return $code;
	}

	/**
	 * Function to start checking right now
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function check_now() {
		if ( get_transient( $this->WPSCAN_TRANSIENT_CRON ) || empty( get_option( $this->OPT_API_TOKEN ) ) ) {
			return;
		}

		// Start cron job and set timeout.
		set_transient( $this->WPSCAN_TRANSIENT_CRON, time(), 60 );

		$this->verify();

		delete_transient( $this->WPSCAN_TRANSIENT_CRON );

		// Notify by mail when solicited.
		$this->classes['notification']->notify();
	}

	/**
	 * Function to verify on WpScan Database for vulnerabilities
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function verify() {
		$ignored = get_option( $this->OPT_IGNORE_ITEMS );

		$ignored = wp_parse_args(
			$ignored,
			array(
				'plugins' => array(),
				'themes'  => array(),
			)
		);

		// Reset errors.
		update_option( $this->OPT_ERRORS, array() );
		update_option( $this->classes['checks/system']->OPT_FATAL_ERRORS, array() );

		// Plugins.
		$this->report['plugins'] = $this->verify_plugins( $ignored['plugins'] );

		// Themes.
		$this->report['themes'] = $this->verify_themes( $ignored['themes'] );

		// WordPress.
		if ( ! isset( $ignored['wordpress'] ) ) {
			$this->report['wordpress'] = $this->verify_wordpress();
		} else {
			$this->report['wordpress'] = array();
		}

		// Security checks.
		if ( get_option( $this->OPT_DISABLE_CHECKS, array() ) !== '1' ) {
			$this->report['security-checks'] = array();

			foreach ( $this->classes['checks/system']->checks as $id => $data ) {
				$data['instance']->perform();
				$this->report['security-checks'][ $id ]['vulnerabilities'] = array();

				if ( $data['instance']->vulnerabilities ) {
					$this->report['security-checks'][ $id ]['vulnerabilities'] = $data['instance']->get_vulnerabilities();

					$this->maybe_fire_issue_found_action( 'security-check', $id, $this->report['security-checks'][ $id ] );
				}
			}
	  }

		// Caching.
		$this->report['cache'] = strtotime( current_time( 'mysql' ) );

		// Saving.
		update_option( $this->OPT_REPORT, $this->report, true );

		// Updates account status (API calls etc).
		$this->classes['account']->update_account_status();
	}

	/**
	 * Fires the wpscan_issue_found action if needed
	 *
	 * @param string $type - The affected component type: plugin, theme, WordPress or security-check
	 * @param string $slug - The affected component slug.
	 *                       For WordPress, it will be the version (ie 5.5.3)
	 *                       For security-checks, it will be the id of the check, ie xmlrpc-enabled
	 * @param array $details - An array containing some keys, such as vulnerabilities
	 * @param array additional_details - An array with the plugin details, such as Version etc
	 **@since 1.14.0
	 *
	 */
	public function maybe_fire_issue_found_action( $type, $slug, $details, $additional_details = array() ) {
		if ( ! count( $details['vulnerabilities'] ) > 0 ) {
			return;
		}

		do_action( $this->WPSCAN_ISSUE_FOUND, $type, $slug, $details, $additional_details );
	}

	/**
	 * Check plugins for any known vulnerabilities
	 *
	 * @param array $ignored - An array of plugins slug to ignore
	 *
	 * @access public
	 * @return array
	 * @since 1.0.0
	 *
	 */
	public function verify_plugins( $ignored ) {
		$plugins = array();
		$ignored[] = 'latest'; // ignore the plugin with the slug 'latest' as this conflicts with our API.

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( get_plugins() as $name => $details ) {
			$slug = $this->get_plugin_slug( $name, $details );

			if ( isset( $ignored[ $slug ] ) ) {
				continue;
			}

			$result = $this->api_get( '/plugins/' . $slug );

			if ( is_object( $result ) ) {
				$plugins[ $slug ]['vulnerabilities'] = $this->get_vulnerabilities( $result, $details['Version'] );

				if ( isset( $result->$slug->closed ) ) {
					$plugins[ $slug ]['closed'] = is_object( $result->$slug->closed ) ? true : false;
				} else {
					$plugins[ $slug ]['closed'] = false;
				}

				$this->maybe_fire_issue_found_action( 'plugin', $slug, $plugins[ $slug ], $details );
			} else {
				if ( 404 === $result ) {
					$plugins[ $slug ]['not_found'] = true;
				}
			}
		}

		return $plugins;
	}

	/**
	 * Check themes for any known vulnerabilities
	 *
	 * @param array $ignored - An array of themes slug to ignore.
	 *
	 * @access public
	 * @return array
	 * @since 1.0.0
	 *
	 */
	public function verify_themes( $ignored ) {
		$themes = array();
		$ignored[] = 'latest'; // ignore the theme with the slug 'latest' as this conflicts with our API.

		if ( ! function_exists( 'wp_get_themes' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
		}

		$filter = array(
			'errors'  => null,
			'allowed' => null,
			'blog_id' => 0,
		);

		foreach ( wp_get_themes( $filter ) as $name => $details ) {
			$slug = $this->get_theme_slug( $name, $details );

			if ( isset( $ignored[ $slug ] ) ) {
				continue;
			}

			$result = $this->api_get( '/themes/' . $slug );

			if ( is_object( $result ) ) {
				$themes[ $slug ]['vulnerabilities'] = $this->get_vulnerabilities( $result, $details['Version'] );

				if ( isset( $result->$slug->closed ) ) {
					$themes[ $slug ]['closed'] = is_object( $result->$slug->closed ) ? true : false;
				} else {
					$themes[ $slug ]['closed'] = false;
				}

				$this->maybe_fire_issue_found_action( 'theme', $slug, $themes[ $slug ], $details );
			} else {
				if ( 404 === $result ) {
					$themes[ $slug ]['not_found'] = true;
				}
			}
		}

		return $themes;
	}

	/**
	 * Check WordPress for any known vulnerabilities.
	 *
	 * @return array
	 * @since 1.0.0
	 * @access public
	 */
	public function verify_wordpress() {
		$wordpress = array();

		$version = get_bloginfo( 'version' );
		$result  = $this->api_get( '/wordpresses/' . str_replace( '.', '', $version ) );

		if ( is_object( $result ) ) {
			$wordpress[ $version ]['vulnerabilities'] = $this->get_vulnerabilities( $result, $version );

			$this->maybe_fire_issue_found_action( 'WordPress', $version, $wordpress[ $version ] );
		}

		return $wordpress;
	}

	/**
	 * Filter vulnerability list from WPScan
	 *
	 * @param array $data - Report data for the element to check.
	 * @param string $version - Installed version.
	 *
	 * @access public
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function get_vulnerabilities( $data, $version ) {
		$list = array();
		$key  = key( $data );

		if ( empty( $data->$key->vulnerabilities ) ) {
			return $list;
		}

		// Trim and remove potential leading 'v'.
		$version = ltrim( trim( $version ), 'v' );

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

	/**
	 * Get vulnerability title.
	 *
	 * @param string $vulnerability - element array.
	 *
	 * @access public
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function get_sanitized_vulnerability_title( $vulnerability ) {
		$title = esc_html( $vulnerability->title ) . ' - ';

		$title .= empty( $vulnerability->fixed_in )
			? __( 'Not fixed', 'wpscan' )
			: sprintf( __( 'Fixed in version %s', 'wpscan' ), esc_html( $vulnerability->fixed_in ) );

		return $title;
	}

	/**
	 * Get the plugin slug for the given name
	 *
	 * @param string $name - plugin name "folder/file.php" or "hello.php".
	 * @param string $details details.
	 *
	 * @access public
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function get_plugin_slug( $name, $details ) {
		$name = $this->get_name( $name );
		$name = $this->get_real_slug( $name, $details['PluginURI'] );

		return sanitize_title( $name );
	}


	/**
	 * Get the theme slug for the given name.
	 *
	 * @param string $name - plugin name "folder/file.php" or "hello.php".
	 * @param string $details details.
	 *
	 * @access public
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function get_theme_slug( $name, $details ) {
		$name = $this->get_name( $name );
		$name = $this->get_real_slug( $name, $details['ThemeURI'] );

		return sanitize_title( $name );
	}

	/**
	 * Get the plugin/theme name
	 *
	 * @param string $name - plugin name "folder/file.php" or "hello.php".
	 *
	 * @access public
	 * @return string
	 * @since 1.0.0
	 *
	 */
	private function get_name( $name ) {
		return strstr( $name, '/' ) ? dirname( $name ) : $name;
	}

	/**
	 * Get plugin/theme slug.
	 *
	 * The name returned by get_plugins or get_themes is not always the real slug.
	 * If the pluginURI is a WordPress url, we take the slug from there.
	 * this also fixes folder renames on plugins if the readme is correct.
	 *
	 * @param string $name - asset name from get_plugins or wp_get_themes.
	 * @param string $url - either the value or ThemeURI or PluginURI.
	 *
	 * @access public
	 * @return string
	 * @since 1.0.0
	 *
	 */
	private function get_real_slug( $name, $url ) {
		$slug  = $name;
		$match = preg_match( '/https?:\/\/wordpress\.org\/(?:extend\/)?(?:plugins|themes)\/([^\/]+)\/?/', $url, $matches );

		if ( 1 === $match ) {
			$slug = $matches[1];
		}

		return sanitize_title( $slug );
	}

	/**
	 * Create a shortcut on Admin Bar to show the total of vulnerabilities found.
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_bar( $wp_admin_bar ) {
		if ( ! current_user_can( $this->WPSCAN_ROLE ) ) {
			return;
		}

		$total = $this->get_total_not_ignored();

		if ( $total > 0 ) {
			$args = array(
				'id'    => 'wpscan',
				'title' => '<span class="ab-icon dashicons-shield"></span><span class="ab-label">' . $total . '</span>',
				'href'  => admin_url( 'admin.php?page=wpscan' ),
				'meta'  => array(
					'title' => sprintf( _n( '%d vulnerability found', '%d vulnerabilities found', $total, 'wpscan' ), $total ),
				),
			);

			$wp_admin_bar->add_node( $args );
		}
	}

	/**
	 * Check if interval scanning is disabled
	 *
	 * @return bool
	 * @since 1.0.0
	 * @access public
	 * @example define('WPSCAN_DISABLE_SCANNING_INTERVAL', true);
	 *
	 */
	public function is_interval_scanning_disabled() {
		if ( defined( 'WPSCAN_DISABLE_SCANNING_INTERVAL' ) ) {
			return WPSCAN_DISABLE_SCANNING_INTERVAL;
		} else {
			return false;
		}
	}

	/**
	 * Delete doing_cron transient, as they could hang in older versions
	 *
	 * @return void
	 * @since 1.12.2
	 * @access public
	 */
	public function delete_doing_cron_transient() {
		delete_transient( $this->WPSCAN_TRANSIENT_CRON );
	}
}
