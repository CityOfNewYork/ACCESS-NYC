<?php

use WPML\LIB\WP\User;

class WPML_TM_REST_Batch_Sync extends WPML_REST_Base {
	/** @var WPML_TP_Batch_Sync_API */
	private $batch_sync_api;

	public function __construct( WPML_TP_Batch_Sync_API $batch_sync_api ) {
		parent::__construct( 'wpml/tm/v1' );
		$this->batch_sync_api = $batch_sync_api;
	}

	public function add_hooks() {
		$this->register_routes();
	}

	public function register_routes() {
		parent::register_route(
			'/tp/batches/sync',
			array(
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'init' ),
				'args'     => array(
					'batchId' => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_batch_ids' ),
						'sanitize_callback' => array( $this, 'sanitize_batch_ids' ),
					),
				),
			)
		);

		parent::register_route(
			'/tp/batches/status',
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'check_progress' ),
			)
		);
	}

	public function init( WP_REST_Request $request ) {
		try {
			return $this->batch_sync_api->init_synchronization( $request->get_param( 'batchId' ) );
		} catch ( Exception $e ) {
			return new WP_Error( 500, $e->getMessage() );
		}
	}

	public function check_progress() {
		try {
			return $this->batch_sync_api->check_progress();
		} catch ( Exception $e ) {
			return new WP_Error( 500, $e->getMessage() );
		}
	}


	public function get_allowed_capabilities( WP_REST_Request $request ) {
		return [ User::CAP_ADMINISTRATOR, User::CAP_MANAGE_TRANSLATIONS, User::CAP_TRANSLATE ];
	}

	public function validate_batch_ids( $batches ) {
		return is_array( $batches );
	}

	public function sanitize_batch_ids( $batches ) {
		return array_map( 'intval', $batches );
	}
}
