<?php
/**
 * Plugin Boostrap class.
 *
 * @package Air_WP_Sync_Pro
 */

namespace Air_WP_Sync_Pro;

use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Admin\Airtable_Admin;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\API\Client;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Formatters\Airtable_Formatter;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Formatters\Format_To_Datetime_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Formatters\Format_To_Raw_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Formatters\Interval_Formatter;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Formatters\Markdown_Formatter;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Formatters\Format_To_String_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Importers\Airtable_Filters;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Importers\Airtable_Importer_Modifier;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Importers\Airtable_Importer_Remote_Data_Provider;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Models\Airtable_Strings_Factory;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Sources\Barcode_Source;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Sources\Collaborator_Source;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Sources\Formula_Source;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Sources\Link_To_Another_Record_Source;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Sources\Unsupported_Source;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Third_Party\Parsedown;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\ActionScheduler\Action_Consumer;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin\Admin;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Post_Module;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Term\Term_Module;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\User\User_Module;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Services;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\WP_Cli\Api_Import_Route;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\WP_Cli\CLI;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Bootstrap_Pro;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Models\Licensing_Data;
use WP_CLI;



/**
 * Class Air_WP_Sync_Bootstrap
 */
class Air_WP_Sync_Bootstrap extends Bootstrap_Pro {

	/**
	 * Init plugin
	 */
	public function init() {
		$this->init_services();
		$this->setup();
		$this->init_language_pack();

		$services = Services::get_instance();

		// Init Modules.
		new Post_Module( $services->get( 'plugin_context' ), $services->get( 'plugin_strings' ) );
		new User_Module( $services->get( 'plugin_context' ) );
		new Term_Module( $services->get( 'plugin_context' ) );

		do_action( 'airwpsync/register_modules' );

		$this->load_importers();

		// Admin.
		if ( is_admin() ) {
			$this->init_admin_pro_features();
			new Airtable_Admin( $services->get( 'plugin_context' ), $services->get( 'airtable_api_client_class_factory' ), $services->get( 'filters' ) );
			new Admin( $services->get( 'options' ), $services->get( 'plugin_context' ), $services->get( 'plugin_strings' ) );
		}

		// Initialize WP_CLI only in cli mode.
		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::add_command( 'air-wp-sync', new CLI() );
		}

		Services::get_instance()->set( 'action_consumer', new Action_Consumer() );

		// Init API.
		new Api_Import_Route();

		$this->init_sources();

		/**
		 * Init Destinations with Formatters.
		 */
		do_action( 'airwpsync/register_destination' );

		if ( defined( 'AIR_WP_SYNC_FORCE_UPDATES' ) && AIR_WP_SYNC_FORCE_UPDATES ) {
			add_filter( 'airwpsync/importer_force_update', '__return_true' );
		}
		// Replace default "format_to_string", "format_to_raw_string" and "format_to_datetime" strategies.
		$services->get( 'formatter' )
			->register_strategy( new Format_To_String_Strategy( $services->get( 'airtable_markdown_formatter' ), $services->get( 'airtable_interval_formatter' ) ) )
			->register_strategy( new Format_To_Raw_Strategy( $services->get( 'airtable_markdown_formatter' ), $services->get( 'formatter' ) ) )
			->register_strategy( new Format_To_Datetime_Strategy() );

		do_action( 'airwpsync/init' );
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

		$services->set( 'plugin_strings', Airtable_Strings_Factory::build_plugin_strings() );

		$services->set( 'licensing_data', new Licensing_Data( AIR_WP_SYNC_PRO_WPC_URL, AIR_WP_SYNC_PRO_WPC_PRODUCT_ID, 'https://wpconnect.co/air-wp-sync-plugin/' ) );

		$services->set( 'filters', new Airtable_Filters() );

		$services->set(
			'airtable_api_client_class_factory',
			static function ( $token ) {
				$skip_cache = defined( 'AIR_WP_SYNC_SKIP_API_CACHE' ) && AIR_WP_SYNC_SKIP_API_CACHE;
				return new Client( $token, $skip_cache );
			}
		);
		$services->set(
			'importer_remote_data_provider_factory',
			function ( $importer ) use ( $services ) {
				return new Airtable_Importer_Remote_Data_Provider( $importer, $services->get( 'airtable_api_client_class_factory' ), $services->get( 'filters' ) );
			}
		);
		$services->set(
			'airtable_importer_modifier',
			new Airtable_Importer_Modifier()
		);

		$services->set( 'formatter', new Airtable_Formatter() );

		$services->set( 'airtable_markdown_formatter', new Markdown_Formatter( new Parsedown() ) );
		$services->set( 'airtable_interval_formatter', new Interval_Formatter() );

		parent::init_services();
	}

	/**
	 * Init Sources.
	 *
	 * @return void
	 */
	public function init_sources() {
		new Barcode_Source();
		new Collaborator_Source();
		new Formula_Source();
		new Link_To_Another_Record_Source();
		new Unsupported_Source();
	}
}
