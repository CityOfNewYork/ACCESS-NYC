<?php
/**
 * Abstract Module.
 * Base class to define custom module (e.g post, user importer).
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Context;

/**
 * Abstract Module
 */
abstract class Abstract_Module {
	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Plugin’s context.
	 *
	 * @var Plugin_Context
	 */
	protected $plugin_context;

	/**
	 * Constructor
	 *
	 * @param Plugin_Context $plugin_context Plugin’s context.
	 *
	 * @throws \Exception If the $slug or the $name property have not been set.
	 */
	public function __construct( $plugin_context ) {
		if ( ! isset( $this->slug ) ) {
			throw new \Exception( esc_html( get_class( $this ) . ' must have a $slug property' ) );
		}
		if ( ! isset( $this->name ) ) {
			throw new \Exception( esc_html( get_class( $this ) . ' must have a $name property' ) );
		}

		$this->plugin_context = $plugin_context;

		add_filter( 'airwpsync/get_modules', array( $this, 'register' ) );

		add_filter( 'admin_init', array( $this, 'register_imported_content_connection_info_box' ) );
	}

	/**
	 * Register module
	 *
	 * @param Abstract_Module[] $modules Registered modules.
	 */
	public function register( $modules ) {
		return array_merge( $modules, array( $this->get_slug() => $this ) );
	}

	/**
	 * Slug getter
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Name getter
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Render module settings
	 *
	 * @param \WP_Post $post Post object holding the importer config.
	 *
	 * @return void
	 */
	abstract public function render_settings( $post );

	/**
	 * Get importer instance
	 *
	 * @param \WP_Post $post Post object holding the importer config.
	 *
	 * @return Abstract_Importer
	 */
	abstract public function get_importer_instance( $post );

	/**
	 * Get mapping options
	 *
	 * @return array
	 */
	abstract public function get_mapping_options();

	/**
	 * Register box to display connection info on imported content.
	 *
	 * @return void
	 */
	abstract public function register_imported_content_connection_info_box();

	/**
	 * Get extra config
	 *
	 * @return array
	 */
	public function get_extra_config() {
		return array();
	}
}
