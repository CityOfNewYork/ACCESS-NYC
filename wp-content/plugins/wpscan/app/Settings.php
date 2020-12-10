<?php

namespace WPScan;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Settings.
 *
 * Handles the plugin's settings page.
 *
 * @since 1.0.0
 */
class Settings
{
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;
		$this->page   = 'wpscan_settings';

		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_notices', array( $this, 'got_api_token' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );

		add_action( 'add_option_' . $this->parent->OPT_SCANNING_INTERVAL, array( $this, 'schedule_event' ), 10, 2 );
		add_action( 'update_option_' . $this->parent->OPT_SCANNING_INTERVAL, array( $this, 'schedule_event' ), 10, 3 );
		add_action( 'update_option_' . $this->parent->OPT_SCANNING_TIME, array( $this, 'schedule_event' ), 10, 3 );

		add_action( 'update_option_' . $this->parent->OPT_IGNORE_ITEMS, array( $this, 'update_ignored_items' ), 10, 3 );
	}

	/**
	 * Introduction
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function introduction() {}

	/**
	 * Register Admin Scripts
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function admin_enqueue( $hook ) {
		$screen = get_current_screen();

		if ( strstr( $screen->id, $this->page ) ) {
			wp_enqueue_style(
				'wpscan-settings',
				plugins_url( 'assets/css/settings.css', WPSCAN_PLUGIN_FILE ),
                array(),
                $this->parent->wpscan_plugin_version()
			);
		}
	}

	/**
	 * Settings Options
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function admin_init() {
		register_setting( $this->page, $this->parent->OPT_API_TOKEN, array( $this, 'sanitize_api_token' ) );
		register_setting( $this->page, $this->parent->OPT_SCANNING_INTERVAL, 'sanitize_text_field' );
		register_setting( $this->page, $this->parent->OPT_SCANNING_TIME, 'sanitize_text_field' );
		register_setting( $this->page, $this->parent->OPT_IGNORE_ITEMS );

		$section = $this->page . '_section';

		add_settings_section(
			$section,
			null,
			array( $this, 'introduction' ),
			$this->page
		);

		add_settings_field(
			$this->parent->OPT_API_TOKEN,
			__( 'WPScan API Token', 'wpscan' ),
			array( $this, 'field_api_token' ),
			$this->page,
			$section
		);

		add_settings_field(
			$this->parent->OPT_SCANNING_INTERVAL,
			__( 'Automated Scanning', 'wpscan' ),
			array( $this, 'field_scanning_interval' ),
			$this->page,
			$section
		);

		add_settings_field(
			$this->parent->OPT_SCANNING_TIME,
			__( 'Scanning Time', 'wpscan' ),
			array( $this, 'field_scanning_time' ),
			$this->page,
			$section
		);

		add_settings_field(
			$this->parent->OPT_IGNORE_ITEMS,
			__( 'Ignore Items', 'wpscan' ),
			array( $this, 'field_ignore_items' ),
			$this->page,
			$section
		);

		if ( $this->parent->is_interval_scanning_disabled() ) {
			wp_clear_scheduled_hook( $this->parent->WPSCAN_SCHEDULE );
		}
	}

	/**
	 * Check if API Token is set
	 *
	 * @since 1.0.0
	 * @access public
	 * @return bool
	 */
	public function api_token_set() {
		$api_token = get_option( $this->parent->OPT_API_TOKEN );

		if ( empty( $api_token ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Warn if no API Token is set
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function got_api_token() {
		$screen = get_current_screen();

		if ( ! $this->api_token_set() && ! strstr( $screen->id, $this->page ) ) {
			printf(
				'<div class="%s"><p>%s <a href="%s">%s</a>%s</p></div>',
				'notice notice-error',
				__( 'To use WPScan you have to setup your WPScan API Token. Either in the ', 'wpscan' ),
				admin_url( 'admin.php?page=' . $this->page ),
				__( 'Settings', 'wpscan' ),
				__( ' page, or, within the wp-config.php file.', 'wpscan' )
			);
		}
	}

	/**
	 * Add submenu
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function menu() {
		$title = __( 'Settings', 'wpscan' );

		add_submenu_page(
			'wpscan',
			$title,
			$title,
			$this->parent->WPSCAN_ROLE,
			$this->page,
			array( $this, 'page' )
		);
	}

	/**
	 * Render the page
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function page() {
		echo '<div class="wrap">';
			echo '<h1><img src="' . $this->parent->plugin_url . 'assets/svg/logo.svg" alt="WPScan"></h1>';

			echo '<h2>' . __( 'Settings', 'wpscan' ) . '</h2>';

			echo '<p>' . __( 'The WPScan WordPress security plugin uses our own constantly updated vulnerability database to stay up to date with the latest WordPress core, plugin and theme vulnerabilities. For the WPScan plugin to retrieve the potential vulnerabilities that may affect your site, you first need to configure your API token, that you can get for free from our database\'s website. Alternatively you can also set your API token in the wp-config.php file using the WPSCAN_API_TOKEN constant.', 'wpscan' ) . '</p><br/>';

			settings_errors();

			echo '<form action="options.php" method="post">';
				settings_fields( $this->page );
				do_settings_sections( $this->page );

				submit_button();
			echo '</form>';
		echo '</div>';
  }

	/**
	 * API token field
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function field_api_token() {
		$api_token = esc_attr( get_option( $this->parent->OPT_API_TOKEN ) );

		if ( defined( 'WPSCAN_API_TOKEN' ) ) {
			$api_token = esc_attr( WPSCAN_API_TOKEN );
			$disabled  = "disabled='true'";
		} else {
			$disabled = null;
		}

		// Field.
		echo "<input type='text' name='" . $this->parent->OPT_API_TOKEN . "' value='$api_token' class='blur-on-lose-focus regular-text' $disabled>";

		// Messages.
		echo '<p class="description">';

		if ( defined( 'WPSCAN_API_TOKEN' ) ) {
			_e( 'Your API Token has been set in a PHP file and been disabled here.', 'wpscan' );
			echo '<br>';
		}

		if ( ! empty( $api_token ) ) {
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

	/**
	 * Scanning interval field
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function field_scanning_interval() {
		$opt_name = $this->parent->OPT_SCANNING_INTERVAL;
		$value    = esc_attr( get_option( $opt_name , 'daily' ) );

		$disabled = $this->parent->is_interval_scanning_disabled() ? "disabled='true'" : null;

		$options = array(
			'daily'      => __( 'Daily', 'wpscan' ),
			'twicedaily' => __( 'Twice daily', 'wpscan' ),
			'hourly'     => __( 'Hourly', 'wpscan' ),
		);

		echo "<select name='$opt_name' $disabled>";
		foreach ( $options as $id => $title ) {
			$selected = selected( $value, $id, false );
			echo "<option value='$id' $selected>$title</option>";
		}
		echo '</select>';

		echo '<p class="description">';

		if ( $this->parent->is_interval_scanning_disabled() ) {
			_e( 'Automated scanning is currently disabled using the <code>WPSCAN_DISABLE_SCANNING_INTERVAL</code> constant.', 'wpscan' );
		} else {
			_e( "This setting will change the frequency that the WPScan plugin will run an automatic scan. This is useful if you want your report, or notifications, to be updated more frequently. Please note that the more frequent scans are run, the more API requests are consumed.", 'wpscan' );
		}

		echo '</p><br>';
	}


	/**
	 * Scanning time field.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function field_scanning_time() {
		$opt      = $this->parent->OPT_SCANNING_TIME;
		$value    = esc_attr( get_option( $opt, '12:00' ) );
		$disabled = $this->parent->is_interval_scanning_disabled() ? "disabled='true'" : null;

		echo "<input type='time' name='$opt' value='$value' $disabled> ";

		if ( ! $this->parent->is_interval_scanning_disabled() ) {
			echo __( 'Current server time is ', 'wpscan' ) . '<code>' . date( 'H:i' ) . '</code>';
		}

		echo '<p class="description">';

		if ( $this->parent->is_interval_scanning_disabled() ) {
			_e( 'Automated scanning is currently disabled using the <code>WPSCAN_DISABLE_SCANNING_INTERVAL</code> constant.', 'wpscan' );
		} else {
			_e( 'This setting allows you to set the scanning hour for the <code>Daily</code> option. For the <code>Twice Daily</code> this will be the first scan and the second will be 12 hours later. For the <code>Hourly</code> it will affect the first scan only.' , 'wpscan' );
		}

		echo "</p><br/>";
	}

	/**
	 * Ignore items field
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function field_ignore_items() {
		$opt   = $this->parent->OPT_IGNORE_ITEMS;
		$value = get_option( $opt, array() );
		$wp    = isset( $value['wordpress'] ) ? 'checked' : null;

		// WordPress.
		echo "<div class='wpscan-ignore-items-section'>";

		echo "<label><input name='{$opt}[wordpress]' type='checkbox' $wp value='1' > " .
			__( 'WordPress Core', 'wpscan' ) . '</label>';

		echo '</div>';

		// Plugins list.
		$this->ignore_items_section( 'plugins', $value );

		// Themes list
		$this->ignore_items_section( 'themes', $value) ;
	}

	/**
	 * Ignore items section
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function ignore_items_section( $type, $value ) {
		$opt = $this->parent->OPT_IGNORE_ITEMS;

		$items = 'themes' === $type
			? wp_get_themes()
			: get_plugins();

		$title = 'themes' === $type
			? __( 'Themes', 'wpscan' )
			: __( 'Plugins', 'wpscan' );


		echo "<div class='wpscan-ignore-items-section'>";

		echo "<h4>$title</h4>";

		foreach ( $items as $name => $details ) {
			$slug = 'themes' === $type
				? $this->parent->get_theme_slug( $name, $details )
				: $this->parent->get_plugin_slug( $name, $details );

			$checked = isset( $value[ $type ][ $slug ] ) ? 'checked' : null;

			echo '<label>' .
					"<input name='{$opt}[$type][$slug]' type='checkbox' $checked value='1'> " .
					esc_html( $details['Name'] ) .
				'</label>';
		}

		echo '</div>';
	}

	/**
	 * Sanitize API token
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function sanitize_api_token( $value ) {
		$value  = trim( $value );
		$result = $this->parent->api_get( '/status', $value );

		$errors = get_option( $this->parent->OPT_ERRORS );

		if ( ! empty( $errors ) ) {
			foreach ( $errors as $error ) {
				add_settings_error(
					$this->page,
					'api_token',
					$error
				);
			}

			update_option( $this->parent->OPT_ERRORS, array() ); // Clear errors.
		} else {
			if ( $this->parent->is_interval_scanning_disabled() ) {
				wp_clear_scheduled_hook( $this->parent->WPSCAN_SCHEDULE );
			}
		}

		return $value;
	}

	/**
	 * Schedule CRON scanning event
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function schedule_event( $old_value, $value ) {
		$api_token = get_option( $this->parent->OPT_API_TOKEN );

		if ( ! empty( $api_token ) && $old_value !== $value ) {
			$interval = esc_attr( get_option( $this->parent->OPT_SCANNING_INTERVAL, 'daily' ) );
			$time     = esc_attr( get_option( $this->parent->OPT_SCANNING_TIME, '12:00 +1day' ) );

			wp_clear_scheduled_hook( $this->parent->WPSCAN_SCHEDULE );

			if ( ! $this->parent->is_interval_scanning_disabled() ) {
				wp_schedule_event( strtotime( $time ), $interval, $this->parent->WPSCAN_SCHEDULE );
			}
		}
	}

	/**
	 * Update ignored items
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function update_ignored_items( $old_value, $value ) {
		$report = $this->parent->get_report();

		if ( empty( $report ) || $old_value === $value ) {
			return;
		}

		foreach ( array( 'themes', 'plugins' ) as $type ) {
			if ( ! isset( $value[ $type ] ) ) {
				continue;
			}

			foreach ( $value[ $type ] as $slug => $checked ) {
				if ( isset( $report[ $type ][ $slug ] ) ) {
					// Remove from the report.
					unset( $report[ $type ][ $slug ] );
				}
			}
		}

		update_option( $this->parent->OPT_REPORT, $report, true );
	}
}
