<?php
/**
 * Taxonomy Destination.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Destinations;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Abstract_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Post_Importer;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Format_Data_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;

/**
 * Class Taxonomy_Destination.
 */
class Taxonomy_Destination extends Abstract_Destination {
	use Format_Data_Destination;

	/**
	 * Destination slug.
	 *
	 * @var string
	 */
	protected $slug = 'taxonomy';

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

		add_action( 'airwpsync/import_record_after', array( $this, 'import' ), 10, 4 );
		add_filter( 'airwpsync/features_by_post_type', array( $this, 'add_features_by_post_type' ), 10, 2 );
	}

	/**
	 * Import terms
	 *
	 * @param Abstract_Importer $importer Importer.
	 * @param array             $fields Fields.
	 * @param \stdClass         $record Remote record.
	 * @param mixed|null        $existing_object_id WordPress object id.
	 */
	public function import( $importer, $fields, $record, $existing_object_id ) {

		$custom_format_strategies = array();
		$mapped_fields            = $this->get_destination_mapping( $importer );
		foreach ( $mapped_fields as $mapped_field ) {
			$taxonomy                              = $mapped_field['wordpress'];
			$custom_format_strategies[ $taxonomy ] = array(
				'pre_format' => function ( $value, $context ) use ( $taxonomy, $mapped_field ) {
					$context->set_args(
						array(
							'taxonomy' => $taxonomy,
							'split_comma_separated_string_into_terms' => ! empty( $mapped_field['options']['form_options_values']['split_comma_separated_string_into_terms'] ),
						)
					);

					if ( is_array( $value ) ) {
						$value = Helper::array_flatten( $value );
					}

					return $value;
				},
				'strategies' => array( 'format_names_to_terms_id' ),
			);
		}

		$context = new Import_Context();
		$context
			->set_importer( $importer )
			->set_existing_object_id( $existing_object_id );

		$terms_id_values = $this->format_data( array(), $context, $fields, $custom_format_strategies );

		foreach ( $terms_id_values as $taxonomy => $terms_id ) {
			wp_set_object_terms( $existing_object_id, $terms_id, $taxonomy );
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
		$features[ $this->slug ] = get_object_taxonomies( $post_type );
		return $features;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_group() {
		return array(
			'label' => __( 'Taxonomies', 'air-wp-sync-pro-plus' ),
			'slug'  => 'taxonomy',
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_mapping_fields() {
		$excluded         = array( 'link_category' );
		$taxonomies       = get_taxonomies(
			array(
				'show_ui' => 1,
			),
			'objects'
		);
		$taxonomy_options = array();

		foreach ( $taxonomies as $taxonomy ) {
			if ( ! in_array( $taxonomy->name, $excluded, true ) ) {
				$taxonomy_options[] = array(
					'value'             => $taxonomy->name,
					'label'             => sprintf( '%s (%s)', $taxonomy->labels->singular_name, $taxonomy->name ),
					'enabled'           => true,
					'form_options'      => array(
						array(
							'name'  => 'split_comma_separated_string_into_terms',
							'type'  => 'checkbox',
							'label' => __( 'Split comma-separated string into terms', 'air-wp-sync-pro-plus' ),
						),
					),
					'supported_sources' => array_merge(
						$this->formatter->get_string_supported_sources(),
						$this->formatter->prefix_proxy_fields_types(
							array_merge(
								$this->formatter->get_single_line_text_supported_sources(),
								$this->formatter->get_single_select_supported_sources(),
								$this->formatter->get_multiple_select_supported_sources()
							)
						)
					),
				);
			}
		}

		return $taxonomy_options;
	}
}
