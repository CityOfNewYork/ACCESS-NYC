<?php

namespace WPML\TM\API;

use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Post;
use WPML_TM_ATE_API;
use WPML_TM_ATE_Jobs;
use function WPML\FP\pipe;

class ATE {
	/** @var WPML_TM_ATE_API $ateApi */
	private $ateApi;

	/** @var WPML_TM_ATE_Jobs $ateJobs */
	private $ateJobs;

	public function __construct( WPML_TM_ATE_API $ateApi, WPML_TM_ATE_Jobs $ateJobs ) {
		$this->ateApi  = $ateApi;
		$this->ateJobs = $ateJobs;
	}

	public function checkJobStatus( $wpmlJobId ) {
		$ateJobId = $this->ateJobs->get_ate_job_id( $wpmlJobId );
		$response = $this->ateApi->get_job_status_with_priority( $ateJobId );

		if ( is_wp_error( $response ) ) {
			return [];
		}

		return wpml_collect( json_decode( wp_json_encode( $response ), true ) )->first(
			pipe(
				Obj::prop( 'ate_job_id' ),
				Relation::equals( $ateJobId )
			)
		);
	}

	public function applyTranslation( $wpmlJobId, $postId, $xliffUrl ) {
		$ateJobId     = $this->ateJobs->get_ate_job_id( $wpmlJobId );
		$xliffContent = $this->ateApi->get_remote_xliff_content( $xliffUrl, [ 'jobId' => $wpmlJobId, 'ateJobId' => $ateJobId ] );

		if ( ! function_exists( 'wpml_tm_save_data' ) ) {
			require_once WPML_TM_PATH . '/inc/wpml-private-actions.php';
		}

		$prevPostStatus = Post::getStatus( $postId );
		if ( $this->ateJobs->apply( $xliffContent ) ) {
			if ( Post::getStatus( $postId ) !== $prevPostStatus ) {
				Post::setStatus( $postId, $prevPostStatus );
			}
			$response = $this->ateApi->confirm_received_job( $ateJobId );

			return ! is_wp_error( $response );
		}

		return false;
	}
}
