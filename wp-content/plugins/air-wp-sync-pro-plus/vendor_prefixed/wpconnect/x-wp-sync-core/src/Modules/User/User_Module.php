<?php
/**
 * User Module.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\User;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin\Imported_Content_Connection_Info;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Imported_Content_Importer_Metas;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Abstract_Module;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\User\Admin\Imported_User_Connection_Info;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Services;

/**
 * Class User_Module.
 */
class User_Module extends Abstract_Module {
	/**
	 * Module slug.
	 *
	 * @var string
	 */
	protected $slug = 'user';

	/**
	 * Module name.
	 *
	 * @var string
	 */
	protected $name = 'User';

	/**
	 * Constructor
	 *
	 * @param Plugin_Context $plugin_context Plugin’s context.
	 */
	public function __construct( $plugin_context ) {
		$this->name = __( 'User', 'air-wp-sync-pro-plus' );
		parent::__construct( $plugin_context );

		add_action( 'admin_enqueue_scripts', array( $this, 'register_styles_scripts' ) );
		add_filter( 'airwpsync/get_l10n_strings', array( $this, 'add_l10n_strings' ) );
		add_action( 'airwpsync/register_destination', array( $this, 'register_destinations' ) );
		add_filter( 'airwpsync/mapping_validation_rules', array( $this, 'add_mapping_validation_rules' ), 10, 1 );
	}

	/**
	 * Register admin styles and scripts
	 */
	public function register_styles_scripts() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'airwpsync-connection' === $screen->id ) {
			wp_enqueue_script( 'air-wp-sync-pro-plus-user-hooks', plugins_url( 'vendor_prefixed/wpconnect/x-wp-sync-core/src/Modules/User/assets/js/hooks.js', $this->plugin_context->get_plugin_file() ), array( 'air-wp-sync-pro-plus-admin-connection' ), $this->plugin_context->get_plugin_version(), false );
		}
	}

	/**
	 * Add module l10n strings
	 *
	 * @param array $l10n_strings Localization strings.
	 *
	 * @return array
	 */
	public function add_l10n_strings( $l10n_strings ) {
		return array_merge(
			$l10n_strings,
			array(
				'requiredUserEmailErrorMessage' => __( 'It is mandatory to map the user e-mail address.', 'air-wp-sync-pro-plus' ),
				'requiredUserLoginErrorMessage' => __( 'It is mandatory to map the Username field.', 'air-wp-sync-pro-plus' ),
			)
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param \WP_Post $post Post object holding the importer config.
	 *
	 * @return void
	 */
	public function render_settings( $post ) {
		$languages = get_available_languages();
		$view      = include_once __DIR__ . '/views/settings.php';
		$view( $languages );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param \WP_Post $post Post object holding the importer config.
	 */
	public function get_importer_instance( $post ) {
		$services = Services::get_instance();
		return new User_Importer( $post, $this, $services->get( 'importer_remote_data_provider_factory' ), $services->get( 'logger' ) );
	}

	/**
	 * Register destinations
	 */
	public function register_destinations() {
		$services = Services::get_instance();
		new User_Destination( $services->get( 'formatter' ) );
		new User_Meta_Destination( $services->get( 'formatter' ) );
	}

	/**
	 * Add required email rule to mapping rules
	 *
	 * @param array $rules Mapping validation rules.
	 */
	public function add_mapping_validation_rules( $rules ) {
		$rules[] = 'requiredUserEmail';
		$rules[] = 'requiredUserLogin';
		return $rules;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_mapping_options() {
		return apply_filters( 'airwpsync/get_wp_fields', array(), $this->slug );
	}

	/**
	 * Register box to display connection info on imported content.
	 *
	 * @return void
	 */
	public function register_imported_content_connection_info_box() {
		new Imported_User_Connection_Info();
	}
}
