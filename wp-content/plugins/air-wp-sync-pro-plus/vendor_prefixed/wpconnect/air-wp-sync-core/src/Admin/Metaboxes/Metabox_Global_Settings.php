<?php
/**
 * Manages Airtable settings options: Token, base / table selection and filters.
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Admin\Metaboxes;

use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Importers\Airtable_Filters;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Services;

/**
 * Class Metabox_Global_Settings
 */
class Metabox_Global_Settings {

	/**
	 * Airtable_Filters class instance.
	 *
	 * @var Airtable_Filters
	 */
	protected $filters;

	/**
	 * Constructor
	 *
	 * @param Airtable_Filters $filters Filters class instance.
	 */
	public function __construct( $filters ) {
		$this->filters = $filters;

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );

		add_action( 'wp_ajax_air_wp_sync_get_airtable_bases', array( $this, 'get_airtable_bases' ) );
		add_action( 'wp_ajax_air_wp_sync_get_airtable_tables', array( $this, 'get_airtable_tables' ) );
		add_action( 'wp_ajax_air_wp_sync_get_airtable_table_users', array( $this, 'get_airtable_table_users' ) );
		add_action( 'wp_ajax_air_wp_sync_get_airtable_table_records', array( $this, 'get_airtable_table_records' ) );
		add_action( 'wp_ajax_air_wp_sync_check_formula_filter', array( $this, 'check_formula_filter' ) );
	}

	/**
	 * Add metabox
	 */
	public function add_meta_box() {
		add_meta_box(
			'airwpsync-global-settings',
			__( 'Airtable Settings', 'air-wp-sync-pro-plus' ),
			array( $this, 'display' ),
			'airwpsync-connection',
			'normal',
			'high'
		);
	}

	/**
	 * Output metabox HTML
	 */
	public function display() {
		$view = include_once __DIR__ . '/views/metabox-airtable-settings.php';
		$view();
	}

	/**
	 * Ajax action to get Airtable bases
	 *
	 * @throws \Exception API key depreciation notice.
	 * @return void
	 */
	public function get_airtable_bases() {
		// Data check.
		if ( empty( $_POST['apiKey'] ) ) {
			wp_die();
		}
		// Nonce check.
		check_ajax_referer( 'air-wp-sync-pro-plus-ajax', 'nonce' );
		Helper::check_ajax_admin_user_access();

		// Get data.
		$params  = array_merge( $_POST );
		$params  = wp_unslash( $params );
		$api_key = sanitize_text_field( $params['apiKey'] );

		try {
			$offset = null;
			$bases  = array();

			if ( strpos( $api_key, 'key' ) === 0 ) {
				/* translators: %1$s = access token creation URL */
				throw new \Exception( sprintf( __( 'This looks like a user API key that is now deprecated. Please replace it with a <a href="%1$s" target="_blank">personal access token</a>.', 'air-wp-sync-pro-plus' ), 'https://airtable.com/developers/web/guides/personal-access-tokens' ) );
			}

			$client = Services::get_instance()->get( 'airtable_api_client_class_factory' )( $api_key );

			do {
				// @TODO: sounds like there is something wrong here, we should pass $options not $offset as param to list_bases?
				$options = array( 'offset' => $offset );
				$result  = $client->list_bases( $offset );
				$offset  = $result->offset ?? null;
				$bases   = array_merge( $bases, $result->bases );
			} while ( ! is_null( $offset ) );

			wp_send_json_success(
				array(
					'bases' => $bases,
				)
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
	 * Ajax action to get Airtable tables.
	 *
	 * @return void
	 */
	public function get_airtable_tables() {
		// Data check.
		if ( empty( $_POST['apiKey'] ) || empty( $_POST['appId'] ) ) {
			wp_die();
		}
		// Nonce check.
		check_ajax_referer( 'air-wp-sync-pro-plus-ajax', 'nonce' );
		Helper::check_ajax_admin_user_access();

		// Get data.
		$params  = array_merge( $_POST );
		$params  = wp_unslash( $params );
		$api_key = sanitize_text_field( $params['apiKey'] );
		$app_id  = sanitize_text_field( $params['appId'] );

		$options = json_decode( sanitize_text_field( $params['options'] ), true );

		try {
			$client = Services::get_instance()->get( 'airtable_api_client_class_factory' )( $api_key );
			$result = $client->get_tables( $app_id );
			$tables = $result->tables;

			foreach ( $tables as &$table ) {
				$table->fields = apply_filters( 'airwpsync/get_table_fields', $table->fields, $app_id, $client, $options );
				foreach ( $table->fields as $field ) {
					$field->name = Helper::decode_emoji( $field->name );
				}
				$table->filters = $this->filters->get_filters_from_fields( $table->fields );
			}

			wp_send_json_success(
				array(
					'tables' => $tables,
				)
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
	 * Ajax action to get users from Airtable collaborator field from a specific table.
	 *
	 * @return void
	 */
	public function get_airtable_table_users() {
		// Data check.
		if ( empty( $_POST['apiKey'] ) || empty( $_POST['appId'] ) || empty( $_POST['table'] ) || empty( $_POST['userFieldName'] ) || ( empty( $_POST['search'] ) && empty( $_POST['search[]'] ) ) ) {
			wp_die();
		}
		// Nonce check.
		check_ajax_referer( 'air-wp-sync-pro-plus-ajax', 'nonce' );
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
			$client = Services::get_instance()->get( 'airtable_api_client_class_factory' )( $api_key );

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
	public function get_airtable_table_records() {
		// Data check.
		if ( empty( $_POST['apiKey'] ) || empty( $_POST['appId'] ) || empty( $_POST['table'] ) || empty( $_POST['recordFieldName'] ) || ( empty( $_POST['search'] ) && empty( $_POST['search[]'] ) ) ) {
			wp_die();
		}
		// Nonce check.
		check_ajax_referer( 'air-wp-sync-pro-plus-ajax', 'nonce' );
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
			$client = Services::get_instance()->get( 'airtable_api_client_class_factory' )( $api_key );

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
	 * Ajax action to check Airtable filters validity.
	 *
	 * @return void
	 */
	public function check_formula_filter() {
		// Data check.
		if ( empty( $_POST['apiKey'] ) || empty( $_POST['appId'] ) || empty( $_POST['table'] ) ) {
			wp_die();
		}
		// Nonce check.
		check_ajax_referer( 'air-wp-sync-pro-plus-ajax', 'nonce' );
		Helper::check_ajax_admin_user_access();

		// Get data.
		$params         = array_merge( $_POST );
		$params         = wp_unslash( $params );
		$api_key        = sanitize_text_field( $params['apiKey'] );
		$app_id         = sanitize_text_field( $params['appId'] );
		$table          = sanitize_text_field( $params['table'] );
		$view           = sanitize_text_field( $params['view'] ?? '' );
		$formula_filter = sanitize_text_field( $params['formulaFilter'] ?? '' );

		$nonce = wp_create_nonce( 'air-wp-sync-pro-plus-ajax' );

		$options = array();
		if ( ! empty( $view ) ) {
			$options['view'] = $view;
		}
		if ( ! empty( $formula_filter ) ) {
			$options['filterByFormula'] = $formula_filter;
		} else {
			wp_send_json_error(
				array(
					'nonce'   => $nonce, // @TODO: to be removed? Does not sound to be used...
					'message' => __( 'No formula to check', 'air-wp-sync-pro-plus' ),
				)
			);
		}

		try {
			$client = Services::get_instance()->get( 'airtable_api_client_class_factory' )( $api_key );
			$client->list_records( $app_id, $table, $options );

			wp_send_json_success(
				array(
					'nonce' => $nonce, // @TODO: to be removed? Does not sound to be used...
				)
			);
		} catch ( \Exception $e ) {
			// TODO: translate error message.

			wp_send_json_error(
				array(
					'nonce' => $nonce,
					'error' => $e->getMessage(),
				)
			);
		}
	}
}
