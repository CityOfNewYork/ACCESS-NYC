<?php

namespace WPML\TM\ATE\Hooks;

use WPML\TM\ATE\ReturnedJobsQueue;

class ReturnedJobActions implements \IWPML_Action {
	/** @var callable :: int->string->void */
	private $addToQueue;

	/** @var callable :: int->string->void */
	private $remove_translation_duplicate_status;

	/**
	 * @param  callable $addToQueue
	 * @param  callable $removeTranslationDuplicateStatus
	 */
	public function __construct( callable $addToQueue, callable $removeTranslationDuplicateStatus ) {
		$this->addToQueue = $addToQueue;
		$this->remove_translation_duplicate_status = $removeTranslationDuplicateStatus;
	}


	public function add_hooks() {
		add_action( 'init', [ $this, 'addToQueue' ] );
	}

	public function addToQueue() {
		if ( isset( $_GET['ate_original_id'] ) ) {
			$ateJobId = (int) $_GET['ate_original_id'];

			if ( isset( $_GET['complete'] ) ) {
				call_user_func( $this->addToQueue, $ateJobId, ReturnedJobsQueue::STATUS_COMPLETED );
				call_user_func( $this->remove_translation_duplicate_status, $ateJobId );
			} elseif ( isset( $_GET['back'] ) ) {
				call_user_func( $this->addToQueue, $ateJobId, ReturnedJobsQueue::STATUS_BACK );
			}
		}
	}
}
