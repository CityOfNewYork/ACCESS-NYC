<?php
/**
 * Manage link between the importer and the Airtable API.
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Importers;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Importer_Remote_Data_Provider;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\API\Client;

/**
 * Airtable_Importer_Remote_Data_Provider class.
 */
class Airtable_Importer_Remote_Data_Provider implements Importer_Remote_Data_Provider {

	/**
	 * The importer.
	 *
	 * @var Abstract_Importer
	 */
	protected $importer;

	/**
	 * API client class factory.
	 *
	 * @var callable
	 */
	protected $api_client_class_factory;

	/**
	 * Airtable_Filters class instance.
	 *
	 * @var Airtable_Filters
	 */
	protected $filters;

	/**
	 * Constructor.
	 *
	 * @param Abstract_Importer $importer The importer.
	 * @param callable          $api_client_class_factory The API client class factory.
	 * @param Airtable_Filters  $filters Airtable_Filters class instance.
	 */
	public function __construct( $importer, $api_client_class_factory, $filters ) {
		$this->importer                 = $importer;
		$this->api_client_class_factory = $api_client_class_factory;
		$this->filters                  = $filters;

		add_filter( 'airwpsync/pre_record_hash', array( $this, 'filter_attachments_urls' ) );
	}

	/**
	 * Get or instantiate Remote API client.
	 *
	 * @return Client
	 */
	public function get_api_client() {
		static $client_instances;
		if ( empty( $client_instances ) ) {
			$client_instances = array();
		}
		$instance_key = $this->importer->config()->get( 'api_key' );
		if ( isset( $client_instances[ $instance_key ] ) ) {
			return $client_instances[ $instance_key ];
		}
		$api_client_class_factory          = $this->api_client_class_factory;
		$client_instances[ $instance_key ] = $api_client_class_factory( $this->importer->config()->get( 'api_key' ) );
		return $client_instances[ $instance_key ];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_table_fields() {
		$app_id = $this->importer->config()->get( 'app_id' );
		$data   = $this->get_api_client()->get_tables( $app_id );
		$table  = Helper::get_table_by_id( $data->tables, $this->importer->config()->get( 'table' ) );

		$fields = $table && $table->fields ? $table->fields : array();

		return apply_filters( 'airwpsync/get_table_fields', $fields, $app_id, $this->get_api_client(), $this->get_import_fields_options() );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $offset Pagination offset.
	 *
	 * @return object
	 */
	public function get_records( $offset ) {
		$formula_filter = $this->importer->config()->get( 'formula_filter' );
		if ( $this->importer->config()->get( 'use_filter_ui' ) ) {
			$formula_filter = $this->filters->build_formula(
				$this->importer->config()->get( 'filters' ),
				$this->filters->get_filters_from_fields( $this->importer->get_run_id() ? $this->importer->get_remote_fields() : $this->importer->get_table_fields() )
			);
		}

		$api_list_options = array(
			'offset'                => $offset,
			'view'                  => $this->importer->config()->get( 'view' ),
			'filterByFormula'       => $formula_filter,
			'returnFieldsByFieldId' => true,
		);
		$api_list_options = apply_filters( 'airwpsync/get_records_api_list_options', $api_list_options, $this->importer );
		// Remove empty values.
		$api_list_options = array_filter( $api_list_options );
		// Get records.
		$app_id   = $this->importer->config()->get( 'app_id' );
		$table_id = $this->importer->config()->get( 'table' );
		$data     = $this->get_api_client()->list_records( $app_id, $table_id, $api_list_options );

		return (object) array(
			'records'     => $data->records,
			'next_offset' => ! empty( $data->offset ) ? $data->offset : false,
		);
	}

	/**
	 * Returns import fields options.
	 *
	 * @return array
	 */
	public function get_import_fields_options() {
		return apply_filters(
			'airwpsync/import_fields_options',
			array(
				'enable_link_to_another_record' => 'yes' === $this->importer->config()->get( 'enable_link_to_another_record' ),
			),
			$this->importer
		);
	}

	/**
	 * Filters the record attachment fields to exclude urls prior to hashing
	 * Airtable URLs change every two hours, causing update problems
	 *
	 * @param array $record_as_array Airtable record.
	 *
	 * @return array
	 */
	public function filter_attachments_urls( $record_as_array ) {
		foreach ( $record_as_array as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = self::filter_attachments_urls( $value );
			}
		}

		return array_filter(
			$record_as_array,
			function ( $value, $key ) {
				return 'url' !== $key;
			},
			ARRAY_FILTER_USE_BOTH
		);
	}
}
