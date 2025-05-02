<?php
/**
 * @author OnTheGo Systems
 */

namespace WPML\TM\ATE\REST;

use WP_REST_Request;
use WPML\Collect\Support\Collection;
use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\TM\API\Jobs;
use WPML\TM\ATE\Download\Process;
use WPML\TM\ATE\Review\PreviewLink;
use WPML\TM\ATE\Review\ReviewStatus;
use WPML\TM\ATE\Review\StatusIcons;
use WPML\TM\ATE\SyncLock;
use WPML\TM\REST\Base;
use WPML_TM_ATE_AMS_Endpoints;
use function WPML\Container\make;
use function WPML\FP\pipe;

class Download extends Base {
	/**
	 * @return array
	 */
	public function get_routes() {
		return [
			[
				'route' => WPML_TM_ATE_AMS_Endpoints::DOWNLOAD_JOBS,
				'args'  => [
					'methods'  => 'POST',
					'callback' => [ $this, 'download' ],
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

	public function download( WP_REST_Request $request ) {
		$lock = make( SyncLock::class );
		if ( ! $lock->create( $request->get_param( 'lockKey' ) ) ) {
			return [];
		}

		return make( Process::class )->run( $request->get_param( 'jobs' ) )->all();
	}
}
