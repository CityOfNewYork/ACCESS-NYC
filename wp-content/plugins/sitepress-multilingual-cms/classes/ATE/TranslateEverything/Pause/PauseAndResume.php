<?php

namespace WPML\TM\ATE\TranslateEverything\Pause;

use WPML\Setup\Option;
use WPML\TranslationMode\Endpoint\SetTranslateEverything;

class PauseAndResume {
	/** @var SetTranslateEverything $set_translate_everything */
	private $set_translate_everything;

	public function __construct(
		SetTranslateEverything $set_translate_everything
	) {
		$this->set_translate_everything = $set_translate_everything;
	}

	public function pause() {
		Option::setIsPausedTranslateEverything( true );
		$this->set_translate_everything->run(
			// Set mark that there is nothing further to translate now.
			wpml_collect( [ 'onlyNew' => true ] )
		);
	}

	public function resume( $translateExisting ) {
		Option::setIsPausedTranslateEverything( false );
		$this->set_translate_everything->run(
			wpml_collect( [ 'onlyNew' => ! $translateExisting ] )
		);
	}
}
