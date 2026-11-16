<?php
/**
 * User Meta Destination.
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
 * Clas User_Meta_Destination
 */
class User_Meta_Destination extends Abstract_Destination {
	use Format_Data_Destination;

	/**
	 * Destination slug
	 *
	 * @var string
	 */
	protected $slug = 'usermeta';

	/**
	 * Module slug
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

		add_action( 'airwpsync/import_record_after', array( $this, 'add_metas' ), 10, 4 );
	}

	/**
	 * Handle user meta importing
	 *
	 * @param Abstract_Importer $importer Importer.
	 * @param array             $fields Fields.
	 * @param \stdClass         $record Remote record.
	 * @param mixed|null        $user_id WordPress object id.
	 */
	public function add_metas( $importer, $fields, $record, $user_id ) {
		$custom_format_strategies = array(
			'custom_field' => array(
				'strategies' => array( 'format_to_raw' ),
			),
		);

		$context = new Import_Context();
		$context
			->set_importer( $importer )
			->set_existing_object_id( $user_id );

		$metas = $this->format_data( array(), $context, $fields, $custom_format_strategies );
		foreach ( $metas as $meta_key => $meta_value ) {
			update_user_meta( $user_id, $meta_key, $meta_value );
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	protected function get_group() {
		return array(
			'slug' => 'user',
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
				'value'             => 'custom_field',
				'label'             => __( 'Custom Field...', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'allow_multiple'    => true,
				'supported_sources' => array_merge( $this->formatter->get_string_supported_sources(), array( 'multipleAttachments' ) ),
			),
		);
	}
}
