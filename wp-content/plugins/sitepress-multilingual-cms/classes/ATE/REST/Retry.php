<?php

namespace WPML\TM\ATE\REST;

use WP_REST_Request;
use WPML\TM\ATE\Retry\Arguments;
use WPML\TM\ATE\Retry\Process;
use WPML\TM\REST\Base;
use WPML_TM_ATE_AMS_Endpoints;
use function WPML\Container\make;

class Retry extends Base {
	/**
	 * @return array
	 */
	public function get_routes() {
		return [
			[
				'route' => WPML_TM_ATE_AMS_Endpoints::RETRY_JOBS,
				'args'  => [
					'methods'  => 'POST',
					'callback' => [ $this, 'retry' ],
				],
			],
		];
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function get_allowed_capabilities( WP_REST_Request $request ) {
		return [
			'manage_options',
			'manage_translations',
			'translate',
		];
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 * @throws \Auryn\InjectionException
	 */
	public function retry( WP_REST_Request $request ) {
		return (array) make( Process::class )->run( $request->get_param( 'jobsToProcess' ) );
	}
}
