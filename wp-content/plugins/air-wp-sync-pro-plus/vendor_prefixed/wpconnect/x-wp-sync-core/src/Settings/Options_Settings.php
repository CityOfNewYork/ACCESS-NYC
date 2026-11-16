<?php
/**
 * Plugin options.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Settings;

/**
 * Class Options_Settings
 */
class Options_Settings extends Abstract_Settings {
	/**
	 * WP option slug
	 *
	 * @var string
	 */
	protected $option_slug;

	/**
	 * Constructor
	 *
	 * @param string $option_slug Option’s slug.
	 */
	public function __construct( $option_slug ) {
		$this->option_slug = $option_slug;
		parent::__construct( $this->load_options() );
	}

	/**
	 * Save settings to DB
	 */
	public function save() {
		update_option( $this->option_slug, $this->settings );
	}

	/**
	 * Load options from DB
	 */
	protected function load_options() {
		return get_option( $this->option_slug );
	}

	/**
	 * Delete options from DB
	 */
	public function delete_options() {
		return delete_option( $this->option_slug );
	}
}
