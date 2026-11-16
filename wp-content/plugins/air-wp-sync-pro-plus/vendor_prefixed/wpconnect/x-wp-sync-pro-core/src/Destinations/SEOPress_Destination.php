<?php
/**
 * Manages import as SEOPress fields.
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
 * SEOPress_Destination class.
 */
class SEOPress_Destination extends Abstract_Destination {
	use Format_Data_Destination;

	/**
	 * Destination slug.
	 *
	 * @var string
	 */
	protected $slug = 'seopress';


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
			'_seopress_social_fb_img_attachment_id'      => array(
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
			'_seopress_social_twitter_img_attachment_id' => array(
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
			'_seopress_titles_desc'                      => array(
				'strategies' => array( 'format_to_string', 'format_to_plain_text' ),
			),
			'_seopress_social_fb_desc'                   => array(
				'strategies' => array( 'format_to_string', 'format_to_plain_text' ),
			),
			'_seopress_social_twitter_desc'              => array(
				'strategies' => array( 'format_to_string', 'format_to_plain_text' ),
			),
			'_seopress_robots_index'                     => array(
				'strategies'  => array( 'format_to_string' ),
				'post_format' => function ( $value ) {
					return ! empty( $value ) ? 'yes' : 'no';
				},
			),
			'_seopress_robots_follow'                    => array(
				'strategies'  => array( 'format_to_string' ),
				'post_format' => function ( $value ) {
					return ! empty( $value ) ? 'yes' : 'no';
				},
			),
			'_seopress_robots_imageindex'                => array(
				'strategies'  => array( 'format_to_string' ),
				'post_format' => function ( $value ) {
					return ! empty( $value ) ? 'yes' : 'no';
				},
			),
			'_seopress_robots_archive'                   => array(
				'strategies'  => array( 'format_to_string' ),
				'post_format' => function ( $value ) {
					return ! empty( $value ) ? 'yes' : 'no';
				},
			),
			'_seopress_robots_snippet'                   => array(
				'strategies'  => array( 'format_to_string' ),
				'post_format' => function ( $value ) {
					return ! empty( $value ) ? 'yes' : 'no';
				},
			),
		);

		$context = new Import_Context();
		$context
			->set_importer( $importer )
			->set_existing_object_id( $post_id );

		$seopress_fields_values = $this->format_data( array(), $context, $fields, $custom_format_strategies );
		foreach ( $seopress_fields_values as $key => $value ) {
			// Enabled redirection if value filled.
			if ( '_seopress_redirections_value' === $key ) {
				update_post_meta( $post_id, '_seopress_redirections_enabled', apply_filters( 'airwpsync/seopress_default_redirections_enabled', 'yes' ) );
				update_post_meta( $post_id, '_seopress_redirections_logged_status', apply_filters( 'airwpsync/seopress_default_redirections_logged_status', 'both' ) );
			}

			if ( ! empty( $value ) && in_array( $key, array( '_seopress_social_fb_img_attachment_id', '_seopress_social_twitter_img_attachment_id' ), true ) ) {
				// Also save image url, width, and height.
				$prefix     = preg_replace( '/\_attachment_id$/', '', $key );
				$media_data = wp_get_attachment_metadata( $value );
				$url        = wp_get_attachment_image_url( $value, 'full' );
				update_post_meta( $post_id, $prefix, $url );
				update_post_meta( $post_id, $prefix . '_width', $media_data['width'] );
				update_post_meta( $post_id, $prefix . '_height', $media_data['height'] );
			}
			update_post_meta( $post_id, $key, $value );
		}

		// In case of empty redirection type meta.
		$redirection_enabled = get_post_meta( $post_id, '_seopress_redirections_enabled', true );
		$redirection_type    = get_post_meta( $post_id, '_seopress_redirections_type', true );
		if ( empty( $redirection_type ) && 'yes' === $redirection_enabled ) {
			update_post_meta( $post_id, '_seopress_redirections_type', apply_filters( 'airwpsync/seopress_default_redirections_type', '301' ) );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_group() {
		return array(
			'label' => __( 'SEOPress', 'air-wp-sync-pro-plus' ),
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
				'_seopress_titles_title',
				'_seopress_titles_desc',
				'_seopress_analysis_target_kw',
				'_seopress_robots_canonical',
				'_seopress_social_fb_title',
				'_seopress_social_fb_desc',
				'_seopress_social_twitter_title',
				'_seopress_social_twitter_desc',
				'_seopress_social_fb_img_attachment_id',
				'_seopress_social_twitter_img_attachment_id',
			);
		}
		$features[ $this->slug ] = $destination_features;
		return $features;
	}

	/**
	 * Get mapping fields
	 */
	protected function get_mapping_fields() {
		return array(
			array(
				'value'             => '_seopress_titles_title',
				'label'             => __( 'Meta Title', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
			),
			array(
				'value'             => '_seopress_titles_desc',
				'label'             => __( 'Meta Description', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_multiple_lines_text_supported_sources() ),
			),
			array(
				'value'             => '_seopress_analysis_target_kw',
				'label'             => __( 'Target keywords', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
			),
			array(
				'value'             => '_seopress_robots_canonical',
				'label'             => __( 'Canonical URL', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_url_supported_sources(),
			),
			array(
				'value'             => '_seopress_social_fb_title',
				'label'             => __( 'Facebook Title', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
			),
			array(
				'value'             => '_seopress_social_fb_desc',
				'label'             => __( 'Facebook Description', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_multiple_lines_text_supported_sources() ),
			),
			array(
				'value'             => '_seopress_social_twitter_title',
				'label'             => __( 'X(Twitter) Title', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
			),
			array(
				'value'             => '_seopress_social_twitter_desc',
				'label'             => __( 'X(Twitter) Description', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_multiple_lines_text_supported_sources() ),
			),
			array(
				'value'             => '_seopress_social_fb_img_attachment_id',
				'label'             => __( 'Facebook Thumbnail', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_files_supported_sources(),
			),
			array(
				'value'             => '_seopress_social_twitter_img_attachment_id',
				'label'             => __( 'X(Twitter) Thumbnail', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_files_supported_sources(),
			),

			array(
				'value'             => '_seopress_robots_index',
				'label'             => __( 'noindex', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_boolean_supported_sources(),
			),
			array(
				'value'             => '_seopress_robots_follow',
				'label'             => __( 'nofollow', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_boolean_supported_sources(),
			),
			array(
				'value'             => '_seopress_robots_imageindex',
				'label'             => __( 'noimageindex', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_boolean_supported_sources(),
			),
			array(
				'value'             => '_seopress_robots_archive',
				'label'             => __( 'noarchive', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_boolean_supported_sources(),
			),
			array(
				'value'             => '_seopress_robots_snippet',
				'label'             => __( 'nosnippet', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_boolean_supported_sources(),
			),
		);
	}
}
