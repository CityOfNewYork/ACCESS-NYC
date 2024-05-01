<?php

namespace WPML\TM\ATE\REST;

use WPML\FP\Relation;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\TM\API\ATE;
use WPML\TM\API\Jobs;
use WPML\TM\ATE\Review\ReviewStatus;
use function WPML\Container\make;
use function WPML\FP\curryN;
use function WPML\FP\pipe;

/**
 * @author OnTheGo Systems
 */
class PublicReceive extends \WPML_TM_ATE_Required_Rest_Base {

	const CODE_UNPROCESSABLE_ENTITY = 422;
	const CODE_OK = 200;

	const ENDPOINT_JOBS_RECEIVE = '/ate/jobs/receive/';

	function add_hooks() {
		$this->register_routes();
	}

	function register_routes() {
		parent::register_route(
			self::ENDPOINT_JOBS_RECEIVE . '(?P<wpmlJobId>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'receive_ate_job' ),
				'args'                => array(
					'wpmlJobId' => array(
						'required'          => true,
						'type'              => 'int',
						'validate_callback' => array( 'WPML_REST_Arguments_Validation', 'integer' ),
						'sanitize_callback' => array( 'WPML_REST_Arguments_Sanitation', 'integer' ),
					),
				),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function get_allowed_capabilities( \WP_REST_Request $request ) {
		return [];
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return true|\WP_Error
	 */
	public function receive_ate_job( \WP_REST_Request $request ) {
		$wpmlJobId = $request->get_param( 'wpmlJobId' );

		$skipEditReviewJobs = Logic::complement( Relation::propEq( 'review_status', ReviewStatus::EDITING ) );

		$ateAPI = make( ATE::class );

		$getXLIFF = pipe(
			Obj::prop( 'job_id' ),
			Fns::safe( [ $ateAPI, 'checkJobStatus' ] ),
			Fns::map( Obj::prop( 'translated_xliff' ) )
		);

		$applyTranslations = Fns::converge(
			Fns::liftA3( curryN( 3, [ $ateAPI, 'applyTranslation' ] ) ),
			[
				Fns::safe( Obj::prop( 'job_id' ) ),
				Fns::safe( Obj::prop( 'original_doc_id' ) ),
				$getXLIFF
			]
		);

		return Maybe::of( $wpmlJobId )
		            ->map( Jobs::get() )
		            ->filter( $skipEditReviewJobs )
		            ->chain( $applyTranslations )
		            ->map( Fns::always( new \WP_REST_Response( null, self::CODE_OK ) ) )
		            ->getOrElse( new \WP_Error( self::CODE_UNPROCESSABLE_ENTITY ) );
	}

	/**
	 * @param int $wpml_job_id
	 *
	 * @return string
	 */
	public static function get_receive_ate_job_url( $wpml_job_id ) {
		return self::get_url( self::ENDPOINT_JOBS_RECEIVE . $wpml_job_id );
	}
}
