<?php
/**
 * Rest API import route.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\WP_Cli;

use WP_REST_Request, WP_REST_Response;
use Exception;
use WP_Error;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;

/**
 * Class Api_Import_Route
 */
class Api_Import_Route extends Api_Abstract_Route {

	/**
	 * Route slug.
	 *
	 * @var string
	 */
	protected $route = 'import/(?P<importer_hash>[^/]+)';

	/**
	 * Route methods.
	 *
	 * @var string
	 */
	protected $methods = array( 'GET', 'POST' );

	/**
	 * Set hooks
	 */
	protected function set_hooks() {
	}

	/**
	 * Get route arguments.
	 *
	 * @return array
	 */
	protected function get_route_args() {
		return array(
			'importer_hash' => array(
				'required'          => true,
				'validate_callback' => array( $this, 'validate_importer_hash' ),
			),
		);
	}

	/**
	 * Validate importer parameter.
	 *
	 * @param mixed            $value Hash value.
	 * @param \WP_REST_Request $request Request.
	 * @param string           $param Param key.
	 *
	 * @return true|\WP_Error
	 */
	public function validate_importer_hash( $value, $request, $param ) {
		return false !== $this->get_importer_by_hash( $value );
	}


	/**
	 * Get importer instance from hash
	 *
	 * @param string $hash Hash value.
	 *
	 * @return bool|Abstract_Importer
	 */
	protected function get_importer_by_hash( $hash ) {
		return array_reduce(
			Helper::get_importers(),
			function ( $result, $importer ) use ( $hash ) {
				return $importer->infos()->get( 'hash' ) === $hash ? $importer : $result;
			},
			false
		);
	}

	/**
	 * Run importer
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @throws Exception No connection found.
	 * @return WP_Error|WP_REST_Response
	 */
	public function run( WP_REST_Request $request ) {
		try {
			$importer_hash = $request->get_param( 'importer_hash' );
			$importer      = $this->get_importer_by_hash( $importer_hash );

			if ( ! $importer ) {
				throw new Exception( 'No connection found.' );
			}
			if ( $importer->get_run_id() ) {
				$importer->set_run_asap( true );
				$importer->log( esc_html__( 'A sync should have been started by a webhook but it has been postponed, a sync is already running.', 'air-wp-sync-pro-plus' ), 'warning' );
			} else {
				// Run a new import.
				$result = $importer->run();
				if ( is_wp_error( $result ) ) {
					throw new Exception( $result->get_error_message() );
				}
			}

			return new WP_REST_Response(
				array(
					'success' => true,
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'error', $e->getMessage() );
		}
	}
}
