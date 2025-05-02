<?php

namespace WPML\TM\ATE\Hooks;

use WPML\FP\Obj;

/**
 * It performs the action at the moment when a user comes back from ATE to WordPress site.
 * Currently, It utilizes the _GET parameters like:
 *  - ate_original_id
 *  - complete
 *
 * We have the access to additional params like:
 *  - complete_no_changes
 *  - ate_status
 *
 * At this moment, we have only one action which changes the status of a job from "duplicated" to "in progress" .
 * @see WPML\TM\ATE\ReturnedJobs::removeJobTranslationDuplicateStatus
 */
class ReturnedJobActions implements \IWPML_Action {
	/** @var callable(int): void */
	private $removeTranslationDuplicateStatus;

	/**
	 * @param callable $removeTranslationDuplicateStatus
	 */
	public function __construct( callable $removeTranslationDuplicateStatus ) {
		$this->removeTranslationDuplicateStatus = $removeTranslationDuplicateStatus;
	}


	public function add_hooks() {
		add_action( 'init', [ $this, 'callActions' ] );
	}

	public function callActions() {
		if ( isset( $_GET['ate_original_id'] ) && Obj::prop( 'complete', $_GET ) ) {
			call_user_func( $this->removeTranslationDuplicateStatus, (int) $_GET['ate_original_id'] );
			do_action( 'wpml_on_back_from_ate_manual_translation', (int) $_GET['ate_original_id'] );
		}
	}
}
