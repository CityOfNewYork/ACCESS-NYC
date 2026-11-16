<?php
/**
 * Manages import as Yoast SEO field.
 *
 * @package wpconnect/x-wp-sync-pro-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Abstract_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Format_Data_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Context;

/**
 * ACF_Destination class.
 */
class Yoast_Destination extends Abstract_Destination {
	use Format_Data_Destination;

	/**
	 * Destination slug.
	 *
	 * @var string
	 */
	protected $slug = 'yoast';


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
	 * Handle post meta importing.
	 *
	 * @param Abstract_Importer $importer  Importer.
	 * @param array             $fields    Fields.
	 * @param \stdClass         $record    The Remote object.
	 * @param int               $post_id   The post id.
	 */
	public function add_metas( $importer, $fields, $record, $post_id ) {
		$custom_format_strategies = array(
			'_yoast_wpseo_opengraph-image'       => array(
				'pre_format'  => function ( $value, $context ) {
					$context->set_args( array( 'attach_to_post_id' => $context->get_existing_object_id() ) );
					return $value;
				},
				'strategies'  => array( 'format_to_one_array_length', 'format_attachments_to_attachments_id', 'format_attachments_id_to_images_id', 'format_to_first_array_item' ),
				'post_format' => function ( $value ) {
					if ( ! empty( $value ) ) {
						$value = wp_get_attachment_image_url( $value, 'full' );
					}
					return $value;
				},
			),
			'_yoast_wpseo_twitter-image'         => array(
				'pre_format'  => function ( $value, $context ) {
					$context->set_args( array( 'attach_to_post_id' => $context->get_existing_object_id() ) );
					return $value;
				},
				'strategies'  => array( 'format_to_one_array_length', 'format_attachments_to_attachments_id', 'format_attachments_id_to_images_id', 'format_to_first_array_item' ),
				'post_format' => function ( $value ) {
					if ( ! empty( $value ) ) {
						$value = wp_get_attachment_image_url( $value, 'full' );
					}
					return $value;
				},
			),
			'_yoast_wpseo_metadesc'              => array(
				'strategies' => array( 'format_to_string', 'format_to_plain_text' ),
			),
			'_yoast_wpseo_opengraph-description' => array(
				'strategies' => array( 'format_to_string', 'format_to_plain_text' ),
			),
			'_yoast_wpseo_twitter-description'   => array(
				'strategies' => array( 'format_to_string', 'format_to_plain_text' ),
			),
		);

		$context = new Import_Context();
		$context
			->set_importer( $importer )
			->set_existing_object_id( $post_id );

		$yoast_fields_values = $this->format_data( array(), $context, $fields, $custom_format_strategies );
		foreach ( $yoast_fields_values as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_group() {
		return array(
			'label' => __( 'Yoast SEO', 'air-wp-sync-pro-plus' ),
			'slug'  => $this->slug,
		);
	}

	/**
	 * Add field features for each post types
	 *
	 * @param array  $features Features list.
	 * @param string $post_type Post type.
	 *
	 * @return string[]
	 */
	public function add_features_by_post_type( $features, $post_type ) {
		$destination_features = array();
		if ( is_post_type_viewable( $post_type ) ) {
			$destination_features = array(
				'_yoast_wpseo_title',
				'_yoast_wpseo_metadesc',
				'_yoast_wpseo_focuskw',
				'_yoast_wpseo_bctitle',
				'_yoast_wpseo_canonical',
				'_yoast_wpseo_opengraph-title',
				'_yoast_wpseo_opengraph-description',
				'_yoast_wpseo_twitter-title',
				'_yoast_wpseo_twitter-description',
				'_yoast_wpseo_opengraph-image',
				'_yoast_wpseo_twitter-image',
			);
		}
		$features[ $this->slug ] = $destination_features;
		return $features;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_mapping_fields() {
		return array(
			array(
				'value'             => '_yoast_wpseo_title',
				'label'             => __( 'SEO Title', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
			),
			array(
				'value'             => '_yoast_wpseo_metadesc',
				'label'             => __( 'Meta Description', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_multiple_lines_text_supported_sources() ),
			),
			array(
				'value'             => '_yoast_wpseo_focuskw',
				'label'             => __( 'Focus Keyphrase', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
			),
			array(
				'value'             => '_yoast_wpseo_bctitle',
				'label'             => __( 'Breadcrumbs Title (Advanced)', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
			),
			array(
				'value'             => '_yoast_wpseo_canonical',
				'label'             => __( 'Canonical URL (Advanced)', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_url_supported_sources(),
			),
			array(
				'value'             => '_yoast_wpseo_opengraph-title',
				'label'             => __( 'Facebook Title', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
			),
			array(
				'value'             => '_yoast_wpseo_opengraph-description',
				'label'             => __( 'Facebook Description', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_multiple_lines_text_supported_sources() ),
			),
			array(
				'value'             => '_yoast_wpseo_twitter-title',
				'label'             => __( 'X(Twitter) Title', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
			),
			array(
				'value'             => '_yoast_wpseo_twitter-description',
				'label'             => __( 'X(Twitter) Description', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_multiple_lines_text_supported_sources() ),
			),
			array(
				'value'             => '_yoast_wpseo_opengraph-image',
				'label'             => __( 'Facebook Image', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_files_supported_sources(),
			),
			array(
				'value'             => '_yoast_wpseo_twitter-image',
				'label'             => __( 'X(Twitter) Image', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_files_supported_sources(),
			),
		);
	}
}
