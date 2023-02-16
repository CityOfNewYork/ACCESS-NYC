<?php

namespace WPML\TM\ATE\Hooks;

use WPML\TM\ATE\ReturnedJobsQueue;

class ReturnedJobActions implements \IWPML_Action {
	/** @var callable :: int->string->void */
	private $addToQueue;

	/**
	 * @param  callable $addToQueue
	 */
	public function __construct( callable $addToQueue ) {
		$this->addToQueue = $addToQueue;
	}


	public function add_hooks() {
		add_action( 'init', [ $this, 'addToQueue' ] );
	}

	public function addToQueue() {
		if ( isset( $_GET['ate_original_id'] ) ) {
			$ateJobId = (int) $_GET['ate_original_id'];

			if ( isset( $_GET['complete'] ) ) {
				call_user_func( $this->addToQueue, $ateJobId, ReturnedJobsQueue::STATUS_COMPLETED );
			} elseif ( isset( $_GET['back'] ) ) {
				call_user_func( $this->addToQueue, $ateJobId, ReturnedJobsQueue::STATUS_BACK );
			}
		}
	}
}
