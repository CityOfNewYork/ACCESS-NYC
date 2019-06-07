<?php
/**
 * Library to show and generate System Info file for WordPress plugins.
 *
 * Greatly inspired (and shares code) from the system info component in Easy Digital Downloads plugin.
 * https://github.com/easydigitaldownloads/easy-digital-downloads
 *
 * @license GPL-2.0+
 * @author Sudar (https://sudarmuthu.com)
 */

namespace Sudar\WPSystemInfo;

if ( class_exists( 'Sudar\\WPSystemInfo\\SystemInfo' ) ) {
	return;
}

/**
 * Shows and generates the System Info file.
 *
 * @since 1.0.0
 */
class SystemInfo {
	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected $plugin_slug = '';

	/**
	 * Config that controls which sections should be displayed.
	 *
	 * @var array
	 */
	protected $config = array();

	/**
	 * SystemInfo constructor.
	 *
	 * @param string $plugin_slug Slug of the plugin.
	 * @param array  $config      (Optional) Configuration options.
	 *
	 * @see SystemInfo::get_default_config for the list of default config information.
	 */
	public function __construct( $plugin_slug, $config = array() ) {
		$this->plugin_slug = $plugin_slug;
		$this->config      = wp_parse_args( $config, $this->get_default_config() );
	}

	/**
	 * Get Default configuration.
	 *
	 * @return array Default configuration.
	 */
	protected function get_default_config() {
		return array(
			'show_post_types'      => true,
			'show_taxonomies'      => true,
			'show_users'           => true,
			'show_plugins'         => true,
			'show_network_plugins' => true,
			'show_session_details' => false,
		);
	}

	/**
	 * Render system info.
	 *
	 * PHPCS is disabled for this function since alignment will mess up the system info output.
	 * phpcs:disable
	 */
	public function render() {
		global $wpdb;
		?>

		<textarea wrap="off" readonly="readonly" name="<?php echo esc_attr( $this->plugin_slug ); ?>-system-info"
		          style="font-family:Menlo,Monaco,monospace; white-space:pre; width:100%; height:500px;" onclick="this.focus();this.select()"
		          title="<?php _e( 'To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac).', 'email-log' ); ?>">
### Begin System Info (Generated at <?php echo current_time( 'Y-m-d H:i:s', true ); ?>) ###

<?php
		/**
		 * Runs before displaying system info.
		 *
		 * This action is primarily for adding extra content in System Info.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_name Plugin slug.
		 */
		do_action( 'before_system_info', $this->plugin_slug );
		do_action( "before_system_info_for_{$this->plugin_slug}" );
?>
-- Site Info --

Site URL:                 <?php echo site_url() . "\n"; ?>
Home URL:                 <?php echo home_url() . "\n"; ?>
Multisite:                <?php echo is_multisite() ? 'Yes' . "\n" : 'No' . "\n"; ?>
Active Theme:             <?php echo $this->get_current_theme_name() . "\n"; ?>

-- WordPress Configuration --

Version:                  <?php echo get_bloginfo( 'version' ) . "\n"; ?>
Language:                 <?php echo get_locale() . "\n"; ?>
Permalink Structure:      <?php echo get_option( 'permalink_structure' ) . "\n"; ?>
WP Table Prefix:          <?php echo $wpdb->prefix, "\n"; ?>
GMT Offset:               <?php echo esc_html( get_option( 'gmt_offset' ) ), "\n"; ?>
Memory Limit:             <?php echo WP_MEMORY_LIMIT; ?><?php echo "\n"; ?>
Memory Max Limit:         <?php echo WP_MAX_MEMORY_LIMIT; ?><?php echo "\n"; ?>
ABSPATH:                  <?php echo ABSPATH . "\n"; ?>
WP_DEBUG:                 <?php echo defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' . "\n" : 'Disabled' . "\n" : 'Not set' . "\n"; ?>
WP_DEBUG_LOG:              <?php echo defined( 'WP_DEBUG_LOG' ) ? WP_DEBUG_LOG ? 'Enabled' . "\n" : 'Disabled' . "\n" : 'Not set' . "\n"; ?>
SAVEQUERIES:              <?php echo defined( 'SAVEQUERIES' ) ? SAVEQUERIES ? 'Enabled' . "\n" : 'Disabled' . "\n" : 'Not set' . "\n"; ?>
WP_SCRIPT_DEBUG:          <?php echo defined( 'WP_SCRIPT_DEBUG' ) ? WP_SCRIPT_DEBUG ? 'Enabled' . "\n" : 'Disabled' . "\n" : 'Not set' . "\n"; ?>
DISABLE_WP_CRON:          <?php echo defined( 'DISABLE_WP_CRON' ) ? DISABLE_WP_CRON ? 'Yes' . "\n" : 'No' . "\n" : 'Not set' . "\n"; ?>
WP_CRON_LOCK_TIMEOUT:     <?php echo defined( 'WP_CRON_LOCK_TIMEOUT' ) ? WP_CRON_LOCK_TIMEOUT : 'Not set', "\n"; ?>
EMPTY_TRASH_DAYS:         <?php echo defined( 'EMPTY_TRASH_DAYS' ) ? EMPTY_TRASH_DAYS : 'Not set', "\n"; ?>

<?php
		$this->print_post_types();
		$this->print_taxonomies();
		$this->print_user_roles();
		$this->print_current_plugins();
		$this->print_network_active_plugins();
		$this->print_web_host_details();
?>
-- User Browser --

User Agent String:        <?php echo esc_html( $_SERVER['HTTP_USER_AGENT'] ), "\n"; ?>

-- Webserver Configuration --

PHP Version:              <?php echo PHP_VERSION . "\n"; ?>
MySQL Version:            <?php echo $wpdb->db_version() . "\n"; ?>
Web Server Info:          <?php echo $_SERVER['SERVER_SOFTWARE'] . "\n"; ?>
Platform:                 <?php echo php_uname( 's' ) . "\n"; ?>

-- PHP Configuration --

PHP Memory Limit:         <?php echo ini_get( 'memory_limit' ) . "\n"; ?>
PHP Safe Mode:            <?php echo ini_get( 'safe_mode' ) ? 'Yes' : 'No', "\n"; // phpcs:ignore PHPCompatibility.PHP.DeprecatedIniDirectives.safe_modeDeprecatedRemoved ?>
PHP Upload Max Size:      <?php echo ini_get( 'upload_max_filesize' ) . "\n"; ?>
PHP Post Max Size:        <?php echo ini_get( 'post_max_size' ) . "\n"; ?>
PHP Upload Max Filesize:  <?php echo ini_get( 'upload_max_filesize' ) . "\n"; ?>
PHP Time Limit:           <?php echo ini_get( 'max_execution_time' ) . "\n"; ?>
PHP Max Input Vars:       <?php echo ini_get( 'max_input_vars' ) . "\n"; // phpcs:ignore PHPCompatibility.PHP.NewIniDirectives.max_input_varsFound ?>
Display Errors:           <?php echo ( ini_get( 'display_errors' ) ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A'; ?><?php echo "\n"; ?>
PHP Arg Separator:        <?php echo ini_get( 'arg_separator.output' ) . "\n"; ?>
PHP Allow URL File Open:  <?php echo ini_get( 'allow_url_fopen' ) ? 'Yes' : 'No', "\n"; ?>

-- PHP Extensions --

fsockopen:                <?php echo ( function_exists( 'fsockopen' ) ) ? 'Your server supports fsockopen.' : 'Your server does not support fsockopen.'; ?><?php echo "\n"; ?>
cURL:                     <?php echo ( function_exists( 'curl_init' ) ) ? 'Your server supports cURL.' : 'Your server does not support cURL.'; ?><?php echo "\n"; ?>
SOAP Client:              <?php echo ( class_exists( 'SoapClient' ) ) ? 'Your server has the SOAP Client enabled.' : 'Your server does not have the SOAP Client enabled.'; ?><?php echo "\n"; ?>
SUHOSIN:                  <?php echo ( extension_loaded( 'suhosin' ) ) ? 'Your server has SUHOSIN installed.' : 'Your server does not have SUHOSIN installed.'; ?><?php echo "\n"; ?>

<?php
		$this->print_session_information();

		/**
		 * Runs after displaying system info.
		 *
		 * This action is primarily for adding extra content in System Info.
		 *
		 * @param string $plugin_name Plugin slug.
		 */
		do_action( 'after_system_info', $this->plugin_slug );
		do_action( "after_system_info_for_{$this->plugin_slug}" );
?>
### End System Info ###</textarea>

		<?php
	}
	// phpcs:enable

	/**
	 * Download System info as a file.
	 *
	 * @param string $file_name (Optional)Name of the file. Default is {plugin slug}-system-info.txt.
	 */
	public function download_as_file( $file_name = '' ) {
		if ( empty( $file_name ) ) {
			$file_name = $this->plugin_slug . '-system-info.txt';
		}

		nocache_headers();

		header( 'Content-type: text/plain' );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );

		echo wp_strip_all_tags( $_POST[ $this->plugin_slug . '-system-info'] );
		die();
	}

	/**
	 * Get current theme name.
	 *
	 * @return string Current theme name.
	 */
	protected function get_current_theme_name() {
		if ( get_bloginfo( 'version' ) < '3.4' ) {
			$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );

			return $theme_data['Name'] . ' ' . $theme_data['Version'];
		}

		$theme_data = wp_get_theme();

		return $theme_data->Name . ' ' . $theme_data->Version;
	}

	/**
	 * Try to identity the hosting provider.
	 *
	 * @return string Web host name if identified, empty string otherwise.
	 */
	protected function print_web_host_details() {
		$host = '';

		if ( defined( 'WPE_APIKEY' ) ) {
			$host = 'WP Engine';
		} elseif ( defined( 'PAGELYBIN' ) ) {
			$host = 'Pagely';
		}

		/**
		 * Filter the identified webhost.
		 *
		 * @since 1.0.0
		 *
		 * @param string $host        Identified web host.
		 * @param string $plugin_name Plugin slug.
		 */
		$host = apply_filters( 'system_info_host', $host, $this->plugin_slug );

		if ( empty( $host ) ) {
			return;
		}

		echo '-- Hosting Provider --', "\n\n";
		echo 'Host:                     ', $host, "\n";
		echo "\n";
	}

	/**
	 * Print plugins that are currently active.
	 */
	protected function print_current_plugins() {
		if ( ! $this->config['show_plugins'] ) {
			return;
		}

		echo "\n";
		echo '-- WordPress Active Plugins --', "\n\n";

		$plugins        = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );

		foreach ( $plugins as $plugin_path => $plugin ) {
			// If the plugin isn't active, don't show it.
			if ( ! in_array( $plugin_path, $active_plugins, true ) ) {
				continue;
			}

			echo $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
		}

		echo "\n";
	}

	/**
	 * Print network active plugins.
	 */
	protected function print_network_active_plugins() {
		if ( ! is_multisite() || ! $this->config['show_network_plugins'] ) {
			return;
		}

		echo "\n";
		echo '-- Network Active Plugins --';

		$plugins        = wp_get_active_network_plugins();
		$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

		foreach ( $plugins as $plugin_path ) {
			$plugin_base = plugin_basename( $plugin_path );

			// If the plugin isn't active, don't show it.
			if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
				continue;
			}

			$plugin = get_plugin_data( $plugin_path );

			echo $plugin['Name'] . ' :' . $plugin['Version'] . "\n";
		}

		echo "\n";
	}

	/**
	 * Print the list of post types and the number of posts in them.
	 *
	 * @param int $spacing Spacing length.
	 */
	protected function print_post_types( $spacing = 26 ) {
		if ( ! $this->config['show_post_types'] ) {
			return;
		}

		$post_types = get_post_types();

		echo 'Registered Post types:    ', implode( ', ', $post_types ), "\n";

		foreach ( $post_types as $post_type ) {
			echo $post_type;

			if ( strlen( $post_type ) < $spacing ) {
				echo str_repeat( ' ', $spacing - strlen( $post_type ) );
			}

			$post_count = wp_count_posts( $post_type );
			foreach ( $post_count as $key => $value ) {
				echo $key, '=', $value, ', ';
			}

			echo "\n";
		}
	}

	/**
	 * Print the list of taxonomies together with term count.
	 */
	protected function print_taxonomies() {
		if ( ! $this->config['show_taxonomies'] ) {
			return;
		}

		echo "\n";
		echo 'Registered Taxonomies:    ';

		$taxonomies = get_taxonomies();
		foreach ( $taxonomies as $taxonomy ) {
			echo $taxonomy, ' (', wp_count_terms( $taxonomy, array( 'hide_empty' => false ) ), '), ';
		}
		echo "\n";
	}

	/**
	 * Print list of user roles and the number of users in each role.
	 */
	protected function print_user_roles() {
		global $wp_roles;

		if ( ! $this->config['show_users'] ) {
			return;
		}

		echo "\n";
		echo 'Users:                    ';

		foreach ( $wp_roles->roles as $role => $role_details ) {
			echo $role_details['name'], ' (', absint( $this->get_user_count_by_role( $role ) ), '), ';
		}
		echo "\n";
	}

	/**
	 * Print session information.
	 */
	protected function print_session_information() {
		if ( ! $this->config['show_session_details'] ) {
			return;
		}
		?>
-- Session Configuration --

Session:                  <?php echo isset( $_SESSION ) ? 'Enabled' : 'Disabled'; ?><?php echo "\n"; ?>
Session Name:             <?php echo esc_html( ini_get( 'session.name' ) ); ?><?php echo "\n"; ?>
Cookie Path:              <?php echo esc_html( ini_get( 'session.cookie_path' ) ); ?><?php echo "\n"; ?>
Save Path:                <?php echo esc_html( ini_get( 'session.save_path' ) ); ?><?php echo "\n"; ?>
Use Cookies:              <?php echo ini_get( 'session.use_cookies' ) ? 'On' : 'Off'; ?><?php echo "\n"; ?>
Use Only Cookies:         <?php echo ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off'; ?><?php echo "\n"; ?>
		<?php
	}

	/**
	 * Get the number of users present in a role.
	 *
	 * @param string $role Role slug.
	 *
	 * @return int Number of users in that role.
	 */
	protected function get_user_count_by_role( $role ) {
		$users_count = count_users();

		$roles = $users_count['avail_roles'];

		if ( ! array_key_exists( $role, $roles ) ) {
			return 0;
		}

		return $roles[ $role ];
	}
}
