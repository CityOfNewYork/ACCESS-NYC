<?php
/**
 * Plugin Main class.
 * Potentially to override, especially the init method to allow plugins to bootstrap only features they support.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin\Admin;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_Attachments_Id_To_Images_Id_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_Attachments_To_Attachments_Id_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_Datetime_To_ISO8601_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_Email_To_User_Id_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_Names_To_Terms_Id_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_Path_To_URL_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_To_Array_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_To_Boolean_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_To_Datetime_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_To_Email_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_To_First_Array_Item_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_To_Int_List_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_To_Integer_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_To_Number_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_To_One_Array_Length_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_To_Plain_Text_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_To_Raw_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_To_String_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Format_URL_To_Post_Id_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\ActionScheduler\Action_Consumer;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Attachments_Formatter;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Terms_Formatter;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Post_Module;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Term\Term_Module;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\User\User_Module;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Settings\Options_Settings;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Logger;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Services;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Language_Packs;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\WP_Cli\Api_Import_Route;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\WP_Cli\CLI;
use WP_CLI;
/**
 * Class Bootstrap
 */
class Bootstrap {
	/**
	 * Available importers
	 *
	 * @var array
	 */
	protected $importers = array();


	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ), 100 );
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ), 100 );  // phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval
		add_filter( 'airwpsync/get_importers', array( $this, 'get_importers' ) );
	}

	/**
	 * Init plugin
	 */
	public function init() {
		$this->init_services();
		$this->init_language_pack();
		$this->setup();

		$services = Services::get_instance();

		// Init Modules.
		new Post_Module( $services->get( 'plugin_context' ), $services->get( 'plugin_strings' ) );
		new User_Module( $services->get( 'plugin_context' ) );
		new Term_Module( $services->get( 'plugin_context' ) );

		do_action( 'airwpsync/register_modules' );

		$this->load_importers();

		// Admin.
		if ( is_admin() ) {
			new Admin( $services->get( 'options' ), $services->get( 'plugin_context' ), $services->get( 'plugin_strings' ) );
		}

		// Initialize WP_CLI only in cli mode.
		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::add_command( 'air-wp-sync-pro-plus', new CLI() );
		}

		Services::get_instance()->set( 'action_consumer', new Action_Consumer() );

		// Init API.
		new Api_Import_Route();

		// Init Sources.
		// ...

		/**
		 * Init Destinations with Formatters.
		 */
		do_action( 'airwpsync/register_destination' );

		do_action( 'airwpsync/init' );
	}

	/**
	 * Load translations.
	 */
	public function init_language_pack() {
		$services = Services::get_instance();
		/**
		 * Plugin’s context.
		 *
		 * @var Plugin_Context $plugin_context
		 */
		$plugin_context        = $services->get( 'plugin_context' );
		$translations_pack_url = $plugin_context->get_translations_pack_url() ?? null;

		if ( $translations_pack_url ) {
			$translations_updater = new Language_Packs( 'plugin', 'air-wp-sync-pro-plus', $translations_pack_url );
			$project              = $translations_updater->add_project();
		} else {
			load_plugin_textdomain( 'air-wp-sync-pro-plus', false, dirname( $plugin_context->get_plugin_basename() ) . '/languages' );
		}
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

		$services->set( 'options', new Options_Settings( 'air_wp_sync_options' ) );

		$services->set( 'logger', new Logger( $services->get( 'plugin_context' )->get_log_dir() ) );

		$services->set( 'attachments_formatter', new Attachments_Formatter() );
		$services->set( 'terms_formatter', new Terms_Formatter() );

		$services->get( 'formatter' )
				->register_strategy( new Format_To_String_Strategy( $services->get( 'formatter' ) ) )
				->register_strategy( new Format_To_Raw_Strategy( $services->get( 'formatter' ) ) )
				->register_strategy( new Format_To_Plain_Text_Strategy() )
				->register_strategy( new Format_Names_To_Terms_Id_Strategy( $services->get( 'formatter' ), $services->get( 'terms_formatter' ) ) )
				->register_strategy( new Format_Attachments_To_Attachments_Id_Strategy( $services->get( 'attachments_formatter' ) ) )
				->register_strategy( new Format_Attachments_Id_To_Images_Id_Strategy() )
				->register_strategy( new Format_To_Number_Strategy() )
				->register_strategy( new Format_To_Integer_Strategy() )
				->register_strategy( new Format_To_Datetime_Strategy() )
				->register_strategy( new Format_Datetime_To_ISO8601_Strategy() )
				->register_strategy( new Format_To_Int_List_Strategy() )
				->register_strategy( new Format_Email_To_User_Id_Strategy() )
				->register_strategy( new Format_To_Email_Strategy() )
				->register_strategy( new Format_URL_To_Post_Id_Strategy() )
				->register_strategy( new Format_To_Array_Strategy() )
				->register_strategy( new Format_Path_To_URL_Strategy() )
				->register_strategy( new Format_To_First_Array_Item_Strategy() )
				->register_strategy( new Format_To_One_Array_Length_Strategy() )
				->register_strategy( new Format_To_Boolean_Strategy() );

		do_action( 'airwpsync/init_services', $services );
	}

	/**
	 * Init Sources.
	 *
	 * @return void
	 */
	public function init_sources() {}

	/**
	 * Add custom cron schedules
	 *
	 * @param array $schedules An array of non-default cron schedules keyed by the schedule name. Default empty array.
	 */
	public function add_cron_schedules( $schedules ) {
		return array_merge(
			$schedules,
			array(
				// phpcs:ignore WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
				'airwpsync_fiveminutes' => array(
					'interval' => 300,
					'display'  => __( 'Every 5 minutes', 'air-wp-sync-pro-plus' ),
				),
				// phpcs:ignore WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
				'airwpsync_tenminutes'  => array(
					'interval' => 600,
					'display'  => __( 'Every 10 minutes', 'air-wp-sync-pro-plus' ),
				),
				'airwpsync_thirtyminutes' => array(
					'interval' => 1800,
					'display'  => __( 'Every 30 minutes', 'air-wp-sync-pro-plus' ),
				),
			)
		);
	}

	/**
	 * Importers getter
	 */
	public function get_importers() {
		return $this->importers;
	}

	/**
	 * Plugin Setup
	 */
	protected function setup() {
		register_post_type(
			'airwpsync-connection',
			array(
				'labels'          => array(
					'name'               => __( 'Connections', 'air-wp-sync-pro-plus' ),
					'singular_name'      => __( 'Connection', 'air-wp-sync-pro-plus' ),
					'add_new'            => __( 'Add New', 'air-wp-sync-pro-plus' ),
					'add_new_item'       => __( 'Add New Connection', 'air-wp-sync-pro-plus' ),
					'edit_item'          => __( 'Edit Connection', 'air-wp-sync-pro-plus' ),
					'new_item'           => __( 'New Connection', 'air-wp-sync-pro-plus' ),
					'view_item'          => __( 'View Connection', 'air-wp-sync-pro-plus' ),
					'search_items'       => __( 'Search Connections', 'air-wp-sync-pro-plus' ),
					'not_found'          => __( 'No Connections found', 'air-wp-sync-pro-plus' ),
					'not_found_in_trash' => __( 'No Connections found in Trash', 'air-wp-sync-pro-plus' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => Admin::TOP_MENU_SLUG,
				'_builtin'        => false,
				'capability_type' => 'post',
				'supports'        => array( 'title', 'custom-fields' ),
				'rewrite'         => false,
				'query_var'       => false,
				'show_in_rest'    => current_user_can( apply_filters( 'airwpsync/manage_options_capability', 'manage_options' ) ),
			)
		);
		register_post_meta(
			'airwpsync-connection',
			'airwpsync_config',
			array(
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			)
		);
	}

	/**
	 * Load available importers
	 */
	protected function load_importers() {
		$post_statuses  = current_user_can( apply_filters( 'airwpsync/manage_options_capability', 'manage_options' ) ) ? array( 'publish', 'draft' ) : array( 'publish' );
		$importer_posts = get_posts(
			array(
				'post_type'      => 'airwpsync-connection',
				'post_status'    => $post_statuses,
				'posts_per_page' => -1,
			)
		);

		foreach ( $importer_posts as $importer_post ) {
			$module_slug = Helper::get_importer_module( $importer_post );
			$module      = Helper::get_module_by_slug( $module_slug );
			if ( $module ) {
				$this->importers[] = $module->get_importer_instance( $importer_post );
			}
		}
	}
}
