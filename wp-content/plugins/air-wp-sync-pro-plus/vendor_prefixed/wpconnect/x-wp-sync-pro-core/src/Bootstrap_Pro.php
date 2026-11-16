<?php
/**
 * Plugin Main class for pro plugins.
 * (includes updater/licensing mechanism and custom text domain logic).
 *
 * @package wpconnect/x-wp-sync-pro-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Admin\License_Form;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Models\Licensing_Data;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Bootstrap;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\EDD\Licensing;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\EDD\Updater;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Settings\Options_Settings;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Services;

/**
 * Class Bootstrap_Pro
 */
class Bootstrap_Pro extends Bootstrap {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'airwpsync/init', array( $this, 'load_updater' ), 0 );
		new Bootstrap_Pro_Plus();
	}

	/**
	 * Initialize Pro features.
	 *
	 * @return void
	 * @throws \Exception @see Services::get.
	 */
	public function init_admin_pro_features() {
		$services = Services::get_instance();
		new License_Form( $services->get( 'options' ), $services->get( 'licensing' ), $services->get( 'plugin_context' ) );
	}

	/**
	 * Init services.
	 * Services are objects that can be shared across the plugin.
	 * They are stored in a container and can be retrieved using the `get` method.
	 *
	 * @return void
	 * @throws \Exception "{$dependency} not found".
	 */
	public function init_services() {
		$services = Services::get_instance();
		/**
		 * Licensing data.
		 *
		 * @var Licensing_Data $licensing_data
		 */
		$licensing_data = $services->get( 'licensing_data' );
		$services->set( 'licensing', new Licensing( $licensing_data ) );

		parent::init_services();
	}

	/**
	 * Use our own translation files for pro version
	 *
	 * @param string $mofile Path to the MO file.
	 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
	 */
	public function load_textdomain_mofile( $mofile, $domain ) {
		if ( 'air-wp-sync-pro-plus' === $domain && false !== strpos( $mofile, WP_LANG_DIR . '/plugins/' ) ) {
			$locale = apply_filters( 'plugin_locale', determine_locale(), $domain );
			$mofile = WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/languages/' . $domain . '-' . $locale . '.mo';
		}

		return $mofile;
	}

	/**
	 * Enable plugin updates
	 */
	public function load_updater() {
		// To support auto-updates, this needs to run during the wp_version_check cron job for privileged users.
		$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;
		if ( current_user_can( 'manage_options' ) || $doing_cron ) {
			$services = Services::get_instance();
			/**
			 * Licensing data.
			 *
			 * @var Licensing_Data $licensing_data
			 * Plugin’s context.
			 * @var Plugin_Context $plugin_context
			 * Options.
			 * @var Options_Settings $options
			 */
			$licensing_data = $services->get( 'licensing_data' );
			$plugin_context = $services->get( 'plugin_context' );
			$options        = $services->get( 'options' );
			// Instantiate self-contained classes.
			new Updater(
				$licensing_data,
				$plugin_context->get_plugin_file(),
				array(
					'version' => $plugin_context->get_plugin_version(),
					'license' => $options->get( 'license_key' ),
					'item_id' => $licensing_data->get_wpc_product_id(),
					'author'  => 'WP connect',
				),
				$options,
				Services::get_instance()->get( 'licensing' )
			);
		}
	}
}
