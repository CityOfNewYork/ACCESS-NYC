<?php

namespace WPML\TM\ATE\Retranslation;

/**
 * The class is responsible for determining if the re-translation should be run and when.
 * It is used inside WPML\TM\ATE\Loader::add_hooks() to schedule the re-translation.
 */
class Scheduler {

	const LAST_CALL_OPTION = 'wpml_ate_retranslation_last_call';

	const INTERVAL = 60 * 2; // 2 minutes

	public function shouldRun(): bool {
		$lastCall = get_option( self::LAST_CALL_OPTION );

		return $lastCall ? ( time() - $lastCall ) > self::INTERVAL : $lastCall;
	}

	public function scheduleNextRun() {
		update_option( self::LAST_CALL_OPTION, time() );
	}

	public function disable() {
		delete_option( self::LAST_CALL_OPTION );
	}
}
