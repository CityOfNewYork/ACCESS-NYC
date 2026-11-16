<?php
/**
 * Manages import as AllInOne SEO fields.
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
 * AIOSEO_Destination class.
 */
class AIOSEO_Destination extends Abstract_Destination {
	use Format_Data_Destination;

	/**
	 * Destination slug.
	 *
	 * @var string
	 */
	protected $slug = 'aioseo';

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
		$post = \AIOSEO\Plugin\Common\Models\Post::getPost( $post_id );

		// Set AIOSEO post if not exists.
		if ( ! $post->exists() ) {
			$post->savePost(
				$post_id,
				array(
					'created'             => gmdate( 'Y-m-d H:i:s' ),
					'updated'             => gmdate( 'Y-m-d H:i:s' ),
					'title'               => '',
					'description'         => '',
					'og_title'            => '',
					'og_description'      => '',
					'twitter_title'       => '',
					'twitter_description' => '',
					'og_article_section'  => '',
				)
			);
		}

		$custom_format_strategies = array(
			'og_image_url'                => array(
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
			'twitter_image_url'           => array(
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
			'_aioseo_description'         => array(
				'strategies' => array( 'format_to_string', 'format_to_plain_text' ),
			),
			'_aioseo_og_description'      => array(
				'strategies' => array( 'format_to_string', 'format_to_plain_text' ),
			),
			'_aioseo_twitter_description' => array(
				'strategies' => array( 'format_to_string', 'format_to_plain_text' ),
			),

			'canonical_url'               => array(
				'strategies'  => array( 'format_to_string' ),
				'post_format' => function ( $value ) {
					return esc_url( $value );
				},
			),

		);

		$context = new Import_Context();
		$context
			->set_importer( $importer )
			->set_existing_object_id( $post_id );

		$aioseo_fields_values = $this->format_data( array(), $context, $fields, $custom_format_strategies );
		foreach ( $aioseo_fields_values as $key => $value ) {
			$db_key = preg_replace( '/^_aioseo_/', '$2', $key );
			$metas  = array(
				$db_key   => $value,
				'updated' => gmdate( 'Y-m-d H:i:s' ),
			);
			if ( in_array( $key, array( 'og_image_url', 'twitter_image_url' ), true ) ) {
				$prefix                                 = explode( '_', $key )[0];
				$metas[ $prefix . '_image_type' ]       = 'custom_image';
				$metas[ $db_key ]                       = wp_get_attachment_image_url( $value, 'full' );
				$metas[ $prefix . '_image_custom_url' ] = $metas[ $db_key ];

				if ( 'og' === $prefix ) {
					$media_data                         = wp_get_attachment_metadata( $value );
					$metas[ $prefix . '_image_width' ]  = $media_data['width'];
					$metas[ $prefix . '_image_height' ] = $media_data['height'];
				}
			} elseif ( 'keyphrases' === $key ) {
				$keyphrases                   = $post->getKeyphrasesDefaults();
				$keyphrases->focus->keyphrase = is_array( $value ) ? implode( ' ', $value ) : $value;
				$metas[ $db_key ]             = wp_json_encode( $keyphrases );
			}

			aioseo()->core->db
				->update( 'aioseo_posts' )
				->set( $metas )
				->where( array( 'post_id' => $post_id ) )
				->run();

			// Set post metas only for specific datas.
			if ( preg_match( '/^_aioseo_/', $key ) ) {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_group() {
		return array(
			'label' => __( 'All in One SEO', 'air-wp-sync-pro-plus' ),
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
				'_aioseo_title',
				'_aioseo_description',
				'_aioseo_og_title',
				'_aioseo_og_description',
				'_aioseo_twitter_title',
				'_aioseo_twitter_description',
				'twitter_use_og',
				'og_image_url',
				'twitter_image_url',
				'canonical_url',
				'keyphrases',
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
				'value'             => '_aioseo_title',
				'label'             => __( 'Meta Title', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
			),
			array(
				'value'             => '_aioseo_description',
				'label'             => __( 'Meta Description', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_multiple_lines_text_supported_sources() ),
			),
			array(
				'value'             => 'keyphrases',
				'label'             => __( 'Focus Keyphrase', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
			),
			array(
				'value'             => 'canonical_url',
				'label'             => __( 'Canonical URL', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_url_supported_sources() ),
			),
			array(
				'value'             => '_aioseo_og_title',
				'label'             => __( 'Facebook Title', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
			),
			array(
				'value'             => '_aioseo_og_description',
				'label'             => __( 'Facebook Description', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_multiple_lines_text_supported_sources() ),
			),
			array(
				'value'             => '_aioseo_twitter_title',
				'label'             => __( 'X(Twitter) Title', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
			),
			array(
				'value'             => '_aioseo_twitter_description',
				'label'             => __( 'X(Twitter) Description', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_multiple_lines_text_supported_sources() ),
			),
			array(
				'value'             => 'og_image_url',
				'label'             => __( 'Facebook Image', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_files_supported_sources(),
			),
			array(
				'value'             => 'twitter_image_url',
				'label'             => __( 'X(Twitter) Image', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_files_supported_sources(),
			),
			array(
				'value'             => 'twitter_use_og',
				'label'             => __( 'Use data from Facebook Tab', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_boolean_supported_sources(),
			),
		);
	}
}
