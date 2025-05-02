<?php

namespace WPML\TM\ATE\Hooks;

use WPML\TM\ATE\ReturnedJobs;
use function WPML\Container\make;
use function WPML\FP\partialRight;

class ReturnedJobActionsFactory implements \IWPML_Backend_Action_Loader, \IWPML_REST_Action_Loader {

	public function create() {
		$ateJobs = make( \WPML_TM_ATE_Jobs::class );

		$removeTranslationDuplicateStatus = partialRight( [ ReturnedJobs::class, 'removeJobTranslationDuplicateStatus' ], [ $ateJobs, 'get_wpml_job_id' ] );

		return new ReturnedJobActions( $removeTranslationDuplicateStatus );
	}
}
