<?php

namespace WPML\TM\ATE\TranslateEverything\Pause;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Right;
use WPML\FP\Left;

class View implements IHandler {
	const ACTION_PAUSE  = 'pause';
	const ACTION_RESUME = 'resume';

	/** @var PauseAndResume $translate_everything */
	private $translate_everything;

	/** @var UserAuthorisation $user */
	private $user;

	public function __construct(
		PauseAndResume $translate_everything,
		UserAuthorisation $user
	) {
		$this->translate_everything = $translate_everything;
		$this->user                 = $user;
	}

	public function run( Collection $data ) {
		$action = $data->get( 'action' );
		switch ( $action ) {
			case self::ACTION_PAUSE:
				return $this->pauseAutomaticTranslation();
			case self::ACTION_RESUME:
				$translateExisting = $data->get( 'translateExisting' );
				return $this->resumeAutomaticTranslation( $translateExisting );
		}

		return $this->unexpectedError();
	}

	private function pauseAutomaticTranslation() {
		if ( ! $this->user->isAllowedToPauseAutomaticTranslation() ) {
			return Left::of(
				__(
					"You're not allowed to pause automatic translation.",
					'sitepress'
				)
			);
		}

		$this->translate_everything->pause();

		return Right::of( true );
	}

	private function resumeAutomaticTranslation( $translateExisting ) {
		if ( ! $this->user->isAllowedToResumeAutomaticTranslation() ) {
			return Left::of(
				__(
					"You're not allowed to start automatic translation.",
					'sitepress'
				)
			);
		}

		$this->translate_everything->resume( $translateExisting );

		return Right::of( true );
	}


	private function unexpectedError() {
		return Left::of(
			__( 'Server error. Please refresh and try again.', 'sitepress' )
		);
	}
}
