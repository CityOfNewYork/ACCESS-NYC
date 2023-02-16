<?php

namespace WPML\ST\StringsCleanup\Ajax;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use function WPML\Container\make;
use WPML\FP\Either;
use WPML\ST\Gettext\AutoRegisterSettings;
use WPML\ST\StringsCleanup\UntranslatedStrings;

class InitStringsRemoving implements IHandler {

	public function run( Collection $data ) {
		$domains = $data->get( 'domains', false );

		if ( $domains !== false ) {

			/** @var UntranslatedStrings $untranslatedStrings */
			$untranslatedStrings = make( UntranslatedStrings::class );

			/** @var AutoRegisterSettings $autoRegsiterSettings */
			$autoRegsiterSettings = make( AutoRegisterSettings::class );

			if ( $autoRegsiterSettings->isEnabled() ) {
				$autoRegsiterSettings->setEnabled( false );
			}

			return Either::of( [
				'total_strings' => $untranslatedStrings->getCountInDomains( $domains )
			] );
		} else {
			return Either::left( __( 'Error: please try again', 'wpml-string-translation' ) );
		}
	}
}
