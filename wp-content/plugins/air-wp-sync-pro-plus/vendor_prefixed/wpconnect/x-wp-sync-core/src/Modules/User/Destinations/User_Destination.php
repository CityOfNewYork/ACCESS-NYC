<?php
/**
 * User Destination.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\User;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Abstract_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Format_Data_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Context;

/**
 * Class User_Destination
 */
class User_Destination extends Abstract_Destination {
	use Format_Data_Destination;

	/**
	 * Destination slug.
	 *
	 * @var string
	 */
	protected $slug = 'user';

	/**
	 * Module slug.
	 *
	 * @var string
	 */
	protected $module = 'user';

	/**
	 * Formatter.
	 *
	 * @var Abstract_Formatter
	 */
	protected $formatter;

	/**
	 * Constructor
	 *
	 * @param Abstract_Formatter $formatter Formatter.
	 */
	public function __construct( $formatter ) {
		parent::__construct();

		$this->formatter = $formatter;

		add_filter( 'airwpsync/import_user_data', array( $this, 'add_to_user_data' ), 20, 3 );
		add_action( 'airwpsync/metabox_mapping_wordpress_after', array( $this, 'add_username_notice' ) );
	}

	/**
	 * Handle user data importing
	 *
	 * @param array             $user_data User data.
	 * @param Abstract_Importer $importer Importer.
	 * @param array             $fields Fields.
	 */
	public function add_to_user_data( $user_data, $importer, $fields ) {
		$custom_format_strategies = array(
			'user_registered' => array( 'strategies' => array( 'format_to_datetime', 'format_datetime_to_iso8601' ) ),
		);

		$context = new Import_Context();
		$context
			->set_importer( $importer );

		$user_data = $this->format_data( $user_data, $context, $fields, $custom_format_strategies );

		return $user_data;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	protected function get_group() {
		return array(
			'label' => __( 'User', 'air-wp-sync-pro-plus' ),
			'slug'  => 'user',
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	protected function get_mapping_fields() {
		return array(
			array(
				'value'             => 'user_login',
				'label'             => __( 'Username', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
				'notice'            => __( 'Please note that usernames cannot be changed once created.', 'air-wp-sync-pro-plus' ),
			),
			array(
				'value'             => 'first_name',
				'label'             => __( 'First name', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'last_name',
				'label'             => __( 'Last name', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'nickname',
				'label'             => __( 'Nickname', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'user_email',
				'label'             => __( 'Email', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'user_url',
				'label'             => __( 'Website', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'description',
				'label'             => __( 'Biographical Info', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'role',
				'label'             => __( 'Role', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'locale',
				'label'             => __( 'Locale', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'user_registered',
				'label'             => __( 'Registered Date', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_datetime_supported_sources(),
			),
		);
	}
}
