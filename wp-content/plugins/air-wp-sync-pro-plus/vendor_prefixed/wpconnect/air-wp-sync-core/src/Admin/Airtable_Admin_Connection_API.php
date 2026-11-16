<?php
/**
 * Specific Airtable admin connection endpoints.
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Admin;

use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Importers\Airtable_Filters;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;

/**
 * Class Airtable_Admin_Connection_API.
 */
class Airtable_Admin_Connection_API {
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
	 * Register API endpoints.
	 *
	 * @param callable         $api_client_class_factory The API client class factory.
	 * @param Airtable_Filters $filters Filters class instance.
	 */
	public function __construct( $api_client_class_factory, $filters ) {
		$this->api_client_class_factory = $api_client_class_factory;
		$this->filters                  = $filters;

		add_action( 'wp_ajax_air_wp_sync_connection_api_airtable_search_users', array( $this, 'airtable_search_users' ) );
		add_action( 'wp_ajax_air_wp_sync_connection_api_airtable_table_records', array( $this, 'airtable_table_records' ) );
		add_action( 'wp_ajax_air_wp_sync_connection_api_check_filters_result', array( $this, 'check_filters_result' ) );
	}

	/**
	 * Get users from Airtable collaborator field from a specific table.
	 *
	 * @return void
	 */
	public function airtable_search_users() {
		// Data check.
		if ( empty( $_POST['apiKey'] ) || empty( $_POST['appId'] ) || empty( $_POST['table'] ) || empty( $_POST['userFieldName'] ) || ( empty( $_POST['search'] ) && empty( $_POST['search[]'] ) ) ) {
			wp_die();
		}

		// Nonce check.
		check_ajax_referer( 'air_wp_sync_connection_api_airtable_search_users', 'nonce' );
		Helper::check_ajax_admin_user_access();

		// Get data.
		$params          = array_merge( $_POST );
		$params          = wp_unslash( $params );
		$api_key         = sanitize_text_field( $params['apiKey'] );
		$app_id          = sanitize_text_field( $params['appId'] );
		$table_id        = sanitize_text_field( $params['table'] );
		$user_field_name = sanitize_text_field( $params['userFieldName'] );
		$search          = sanitize_text_field( $params['search'] ?? '' );
		$search_multi    = ! empty( $params['search[]'] ) ? array_map( 'sanitize_text_field', $params['search[]'] ) : array();
		try {
			$api_client_class_factory = $this->api_client_class_factory;
			$client                   = $api_client_class_factory( $api_key );

			$filter_by_formula = '';
			if ( ! empty( $search_multi ) ) {
				$filter_by_formula = 'OR(';
				foreach ( $search_multi as $search_term ) {
					$filter_by_formula .= sprintf(
						'FIND(%s,%s)',
						$this->filters->escape_formula_column_value( $search_term ),
						$this->filters->escape_formula_column_name( $user_field_name )
					) . ',';
				}
				$filter_by_formula  = rtrim( $filter_by_formula, ',' );
				$filter_by_formula .= ')';
			} else {
				$filter_by_formula .= sprintf(
					'FIND(%s,%s)',
					$this->filters->escape_formula_column_value( $search ),
					$this->filters->escape_formula_column_name( $user_field_name )
				);
			}

			$result = $client->list_records(
				$app_id,
				$table_id,
				array(
					'pageSize'        => 10,
					'fields'          => array( $user_field_name ),
					'filterByFormula' => $filter_by_formula,
				)
			);

			$users = array_map(
				function ( $record ) use ( $user_field_name ) {
					return $record->fields->{$user_field_name};
				},
				$result->records ?? array()
			);

			wp_send_json_success(
				$users
			);
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Ajax action to get records from Airtable link to another record field from a specific table.
	 *
	 * @return void
	 */
	public function airtable_table_records() {
		// Data check.
		if ( empty( $_POST['apiKey'] ) || empty( $_POST['appId'] ) || empty( $_POST['table'] ) || empty( $_POST['recordFieldName'] ) || ( empty( $_POST['search'] ) && empty( $_POST['search[]'] ) ) ) {
			wp_die();
		}
		// Nonce check.
		check_ajax_referer( 'air_wp_sync_connection_api_airtable_table_records', 'nonce' );
		Helper::check_ajax_admin_user_access();

		// Get data.
		$params            = array_merge( $_POST );
		$params            = wp_unslash( $params );
		$api_key           = sanitize_text_field( $params['apiKey'] );
		$app_id            = sanitize_text_field( $params['appId'] );
		$table_id          = sanitize_text_field( $params['table'] );
		$record_field_name = sanitize_text_field( $params['recordFieldName'] );
		$search            = sanitize_text_field( $params['search'] ?? '' );
		$search_multi      = ! empty( $params['search[]'] ) ? array_map( 'sanitize_text_field', $params['search[]'] ) : array();
		try {
			$api_client_class_factory = $this->api_client_class_factory;
			$client                   = $api_client_class_factory( $api_key );

			$filter_by_formula = '';
			if ( ! empty( $search_multi ) ) {
				$filter_by_formula = 'OR(';
				foreach ( $search_multi as $search_term ) {
					$filter_by_formula .= sprintf(
						'FIND(%s,%s)',
						$this->filters->escape_formula_column_value( $search_term ),
						$this->filters->escape_formula_column_name( $record_field_name )
					) . ',';
				}
				$filter_by_formula  = rtrim( $filter_by_formula, ',' );
				$filter_by_formula .= ')';
			} else {
				$filter_by_formula .= sprintf(
					'FIND(%s,%s)',
					$this->filters->escape_formula_column_value( $search ),
					$this->filters->escape_formula_column_name( $record_field_name )
				);
			}

			$result = $client->list_records(
				$app_id,
				$table_id,
				array(
					'pageSize'        => 10,
					'fields'          => array( $record_field_name ),
					'filterByFormula' => $filter_by_formula,
					'cellFormat'      => 'string',
					'timeZone'        => 'GMT',
					'userLocale'      => 'en-gb',
				)
			);

			$records_value = array_reduce(
				$result->records ?? array(),
				function ( $carry, $record ) use ( $record_field_name, $search ) {
					$records_values = array_map( 'trim', explode( ',', $record->fields->{$record_field_name} ) );
					// Filter again the values, some of them might have been added because there are in the same cell.
					$records_values = array_filter(
						$records_values,
						function ( $field_records_value ) use ( $search ) {
							return mb_strpos( $field_records_value, $search ) !== false;
						}
					);
					$records_values = array_values( $records_values );
					return array_merge( $carry, $records_values );
				},
				array()
			);

			$records_value = array_unique( $records_value );

			$records_value = array_map(
				function ( $record_value ) {
					return array(
						'id'   => $record_value,
						'name' => $record_value,
					);
				},
				$records_value
			);

			wp_send_json_success(
				$records_value
			);
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error' => $e->getMessage(),
				)
			);
		}
	}


	/**
	 * Ajax action to get approximative records count from Airtable based on filters.
	 *
	 * @return void
	 */
	public function check_filters_result() {
		// Nonce check.
		check_ajax_referer( 'air_wp_sync_connection_api_check_filters_result', 'nonce' );
		Helper::check_ajax_admin_user_access();

		$params         = wp_unslash( $_POST );
		$api_key        = sanitize_text_field( $params['apiKey'] );
		$app_id         = sanitize_text_field( $params['appId'] );
		$table          = sanitize_text_field( $params['table'] );
		$view           = sanitize_text_field( $params['view'] ?? '' );
		$product_type   = sanitize_text_field( $params['product_type'] ?? '' );
		$formula_filter = sanitize_text_field( $params['formulaFilter'] ?? '' );
		$use_filter_ui  = (bool) ( $params['use_filter_ui'] ?? '' );
		$filters        = sanitize_text_field( $params['filters'] ?? '' );
		if ( ! empty( $filters ) ) {
			$filters = json_decode( $filters );
		}

		$module_slug = sanitize_text_field( $params['module'] ?? '' );
		$module      = Helper::get_module_by_slug( $module_slug );

		if ( ! $module ) {
			wp_send_json_error(
				__( 'An error occurred, please try again.', 'air-wp-sync-pro-plus' )
			);
		}
		try {
			$importer_post = new \WP_Post(
				(object) array(
					'ID'                => 0,
					'post_name'         => 'temp',
					'post_title'        => 'Temp',
					'post_modified_gmt' => '0000-00-00 00:00:00',
					'post_content'      => wp_json_encode(
						array(
							'api_key'        => $api_key,
							'app_id'         => $app_id,
							'table'          => $table,
							'view'           => $view,
							'product_type'   => $product_type,
							'formula_filter' => $formula_filter,
							'use_filter_ui'  => $use_filter_ui,
							'filters'        => $filters,
						)
					),
				)
			);
			$importer      = $module->get_importer_instance( $importer_post );
			$result        = $importer->get_remote_data_provider()->get_records( 0 );
			if ( ! empty( $result->records ) ) {
				$count_records = count( $result->records );
				if ( $result->next_offset ) {
					wp_send_json_success(
						sprintf(
						/* Translators: %d how many results will be returned from Airtable */
							__(
								'These filters will return more than %d results.',
								'air-wp-sync-pro-plus'
							),
							number_format_i18n( $count_records )
						)
					);
				} else {
					wp_send_json_success(
						sprintf(
						/* Translators: %d how many results will be returned from Airtable */
							_n(
								'These filters will return %d result.',
								'These filters will return %d results.',
								$count_records,
								'air-wp-sync-pro-plus'
							),
							number_format_i18n( $count_records )
						)
					);
				}
			} else {
				wp_send_json_error(
					__( 'These filters will return no result.', 'air-wp-sync-pro-plus' )
				);
			}
		} catch ( \Throwable $exception ) {
			wp_send_json_error(
				__( 'An error occurred, please try again.', 'air-wp-sync-pro-plus' )
			);
		}
	}
}
