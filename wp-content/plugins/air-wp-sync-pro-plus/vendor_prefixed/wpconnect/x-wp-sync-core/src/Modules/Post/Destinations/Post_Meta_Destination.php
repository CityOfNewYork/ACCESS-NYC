<?php
/**
 * Post Meta Destination.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Destinations;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Abstract_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Format_Data_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Context;

/**
 * Class Post_Meta_Destination.
 */
class Post_Meta_Destination extends Abstract_Destination {
	use Format_Data_Destination;

	/**
	 * Destination slug.
	 *
	 * @var string
	 */
	protected $slug = 'postmeta';

	/**
	 * Module slug.
	 *
	 * @var string
	 */
	protected $module = 'post';

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
		add_filter( 'airwpsync/features_by_post_type', array( $this, 'add_features_by_post_type' ), 10, 2 );
	}

	/**
	 * Handle post meta importing
	 *
	 * @param Abstract_Importer $importer Importer.
	 * @param array             $fields Fields.
	 * @param \stdClass         $record Remote record.
	 * @param mixed|null        $existing_object_id WordPress object id.
	 */
	public function add_metas( $importer, $fields, $record, $existing_object_id ) {
		$custom_format_strategies = array(
			'_thumbnail_id' => array(
				'pre_format' => function ( $value, $context ) {
					$context->set_args( array( 'attach_to_post_id' => $context->get_existing_object_id() ) );
					return $value;
				},
				'strategies' => array( 'format_to_one_array_length', 'format_attachments_to_attachments_id', 'format_attachments_id_to_images_id', 'format_to_first_array_item' ),
			),
			'custom_field'  => array(
				'strategies' => array( 'format_to_raw' ),
			),
		);

		$context = new Import_Context();
		$context
			->set_importer( $importer )
			->set_existing_object_id( $existing_object_id );

		$metas = $this->format_data( array(), $context, $fields, $custom_format_strategies );
		foreach ( $metas as $meta_key => $meta_value ) {
			update_post_meta( $existing_object_id, $meta_key, $meta_value );
		}
	}

	/**
	 * Add field features for each post types
	 *
	 * @param array  $features Features.
	 * @param string $post_type Post type.
	 *
	 * @return array
	 */
	public function add_features_by_post_type( $features, $post_type ) {
		$destination_features = array(
			'custom_field',
		);

		if ( post_type_supports( $post_type, 'thumbnail' ) ) {
			$destination_features[] = '_thumbnail_id';
		}
		$features[ $this->slug ] = $destination_features;
		return $features;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_group() {
		return array(
			'slug' => 'post',
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_mapping_fields() {
		return array(
			array(
				'value'             => '_thumbnail_id',
				'label'             => __( 'Featured Image', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_files_supported_sources(),
			),
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
