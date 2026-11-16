<?php
/**
 * Term Destination.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Term;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Abstract_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Format_Data_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Context;

/**
 * Class Term_Destination
 */
class Term_Destination extends Abstract_Destination {
	use Format_Data_Destination;

	/**
	 * Destination slug.
	 *
	 * @var string
	 */
	protected $slug = 'term';

	/**
	 * Module slug.
	 *
	 * @var string
	 */
	protected $module = 'term';

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

		add_filter( 'airwpsync/import_term_data', array( $this, 'add_to_term_data' ), 20, 5 );
		add_filter( 'airwpsync/import_record_after', array( $this, 'check_parent_relationship' ), 10, 4 );
		add_filter( 'airwpsync/features_by_taxonomy', array( $this, 'add_features_by_taxonomy' ), 10, 2 );
	}

	/**
	 * Handle term data importing.
	 *
	 * @param array             $term_data Term data.
	 * @param Abstract_Importer $importer Term importer.
	 * @param array             $fields Fields.
	 * @param \stdClass         $record Remote record.
	 * @param int               $term_id Existing content id.
	 */
	public function add_to_term_data( $term_data, $importer, $fields, $record, $term_id ) {

		$custom_format_strategies = array(
			'name'       => array( 'strategies' => array( 'format_to_string', 'format_to_plain_text' ) ),
			'parentById' => array( 'strategies' => array( 'format_to_number' ) ),
		);

		$context = new Import_Context();
		$context
			->set_importer( $importer )
			->set_existing_object_id( $term_id );

		$term_data = $this->format_data( $term_data, $context, $fields, $custom_format_strategies );

		$taxonomy = ! empty( $term_data['taxonomy'] ) ? $term_data['taxonomy'] : $importer->config()->get( 'taxonomy' );

		foreach ( $term_data as $field_name => $field_value ) {
			if ( 'parentByName' === $field_name ) {
				$term                = get_term_by( 'name', $field_value, $taxonomy );
				$field_value         = $term ? $term->term_id : 0;
				$term_data['parent'] = $field_value;
				break;
			} elseif ( 'parentById' === $field_name ) {
				$term                = get_term_by( 'id', $field_value, $taxonomy );
				$field_value         = $term ? $term->term_id : 0;
				$term_data['parent'] = $field_value;
				break;
			}
		}

		return $term_data;
	}

	/**
	 * Check parent relationship, and forces an update if parentName or parentById is mapped but term is not found.
	 *
	 * @param Abstract_Importer $importer Importer.
	 * @param array             $fields Fields.
	 * @param \stdClass         $record Remote record.
	 * @param mixed|null        $term_id Term ID.
	 */
	public function check_parent_relationship( $importer, $fields, $record, $term_id ) {
		$term_data = $this->add_to_term_data( array(), $importer, $fields, $record, $term_id );
		if ( ( ! empty( $term_data['parentByName'] ) || ! empty( $term_data['parentById'] ) ) && 0 === $term_data['parent'] ) {
			delete_term_meta( $term_id, '_air_wp_sync_hash' );
		}
	}

	/**
	 * Add field features for each taxonomy
	 *
	 * @param array        $features  Features.
	 * @param \WP_Taxonomy $taxonomy  Taxonomy term object.
	 *
	 * @return array
	 */
	public function add_features_by_taxonomy( $features, $taxonomy ) {
		$destination_features = array(
			'name',
			'slug',
			'taxonomy',
			'description',
		);

		if ( $taxonomy->hierarchical ) {
			$destination_features[] = 'parentById';
			$destination_features[] = 'parentByName';
		}

		$features[ $this->slug ] = $destination_features;
		return $features;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	protected function get_group() {
		return array(
			'label' => __( 'Terms', 'air-wp-sync-pro-plus' ),
			'slug'  => 'term',
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
				'value'             => 'name',
				'label'             => __( 'Name', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'slug',
				'label'             => __( 'Slug', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'taxonomy',
				'label'             => __( 'Taxonomy', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'description',
				'label'             => __( 'Description', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			array(
				'value'             => 'parentById',
				'label'             => __( 'Parent (by ID)', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => array_merge(
					$this->formatter->get_single_line_text_supported_sources(),
					$this->formatter->get_single_select_supported_sources(),
					$this->formatter->get_number_supported_sources()
				),
			),
			array(
				'value'             => 'parentByName',
				'label'             => __( 'Parent (by name)', 'air-wp-sync-pro-plus' ),
				'enabled'           => true,
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
		);
	}
}
