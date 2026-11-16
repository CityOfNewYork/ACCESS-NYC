<?php
/**
 * Plugin_Context.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models;

/**
 * Class Plugin_Context
 */
class Plugin_Context {

	/**
	 * Plugin’s name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Front prefix: used in css/html.
	 *
	 * @var string
	 */
	private $admin_menu_name;

	/**
	 * Plugin’s vendor prefixed folder URL.
	 *
	 * @var string
	 */
	private $plugin_vendor_prefixed_url;

	/**
	 * Plugin’s file path (__FILE__ from main plugin file).
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Plugin’s dir path (plugin_dir_path( __FILE__ ) from main plugin file).
	 *
	 * @var string
	 */
	private $plugin_dir;

	/**
	 * Plugin’s version.
	 *
	 * @var string
	 */
	private $plugin_version;

	/**
	 * Plugin’s basename.
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Log directory path.
	 *
	 * @var string
	 */
	private $log_dir;

	/**
	 * Translations pack URL.
	 *
	 * @var string
	 */
	private $translations_pack_url;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $admin_menu_name Admin menu name.
	 * @param string $plugin_vendor_prefixed_url Plugin’s vendor prefixed folder URL.
	 * @param string $plugin_file Plugin’s file path.
	 * @param string $plugin_dir Plugin’s dir path.
	 * @param string $plugin_version Plugin’s version.
	 * @param string $plugin_basename Plugin’s basename.
	 * @param string $log_dir Log directory path.
	 * @param string $translations_pack_url Translations pack URL.
	 */
	public function __construct( $plugin_name, $admin_menu_name, $plugin_vendor_prefixed_url, $plugin_file, $plugin_dir, $plugin_version, $plugin_basename, $log_dir, $translations_pack_url ) {
		$this->plugin_name                = $plugin_name;
		$this->admin_menu_name            = $admin_menu_name;
		$this->plugin_vendor_prefixed_url = $plugin_vendor_prefixed_url;
		$this->plugin_file                = $plugin_file;
		$this->plugin_dir                 = $plugin_dir;
		$this->plugin_version             = $plugin_version;
		$this->plugin_basename            = $plugin_basename;
		$this->log_dir                    = $log_dir;
		$this->translations_pack_url      = $translations_pack_url;
	}

	/**
	 * Returns plugin’s name.
	 *
	 * @return string
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * Returns admin menu name.
	 *
	 * @return string
	 */
	public function get_admin_menu_name(): string {
		return $this->admin_menu_name;
	}

	/**
	 * Returns plugin’s vendor prefixed URL.
	 *
	 * @return string
	 */
	public function get_plugin_vendor_prefixed_url(): string {
		return $this->plugin_vendor_prefixed_url;
	}

	/**
	 * Returns plugin’s file.
	 *
	 * @return string
	 */
	public function get_plugin_file(): string {
		return $this->plugin_file;
	}

	/**
	 * Returns plugin’s dir.
	 *
	 * @return string
	 */
	public function get_plugin_dir(): string {
		return $this->plugin_dir;
	}

	/**
	 * Returns plugin’s version.
	 *
	 * @return string
	 */
	public function get_plugin_version(): string {
		return $this->plugin_version;
	}

	/**
	 * Returns plugin’s basename.
	 *
	 * @return string
	 */
	public function get_plugin_basename(): string {
		return $this->plugin_basename;
	}

	/**
	 * Returns plugin’s log directory.
	 *
	 * @return string
	 */
	public function get_log_dir(): string {
		return $this->log_dir;
	}

	/**
	 * Returns translations pack URL.
	 *
	 * @return string
	 */
	public function get_translations_pack_url(): string {
		return $this->translations_pack_url;
	}

	/**
	 * Build Plugin_Context from array.
	 *
	 * @param array $props Instance props.
	 *
	 * @return Plugin_Context
	 * @throws \Exception Plugin_Context::from_array : missing props %s.
	 */
	public static function from_array( $props ) {
		$expected_props_keys = array_flip( array( 'plugin_name', 'admin_menu_name', 'plugin_vendor_prefixed_url', 'plugin_file', 'plugin_dir', 'plugin_version', 'plugin_basename', 'log_dir', 'translations_pack_url' ) );
		$missing_props       = array_diff_key( $expected_props_keys, $props );
		if ( count( $missing_props ) > 0 ) {
			throw new \Exception( esc_html( sprintf( 'Plugin_Context::from_array : missing props %s', implode( ', ', $missing_props ) ) ) );
		}
		return new Plugin_Context(
			$props['plugin_name'],
			$props['admin_menu_name'],
			$props['plugin_vendor_prefixed_url'],
			$props['plugin_file'],
			$props['plugin_dir'],
			$props['plugin_version'],
			$props['plugin_basename'],
			$props['log_dir'],
			$props['translations_pack_url']
		);
	}
}
