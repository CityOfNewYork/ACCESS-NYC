<?php

namespace WPML\TM\API;

use WPML\Element\API\Languages;
use WPML\LIB\WP\User;
use WPML\Setup\Option;
use function WPML\Container\make;

class Basket {
	/**
	 * @return bool
	 */
	public static function shouldUse( $currentLanguageCode = null ) {
		$doesNotHaveUserForEachLanguage = function () use ( $currentLanguageCode ) {
			global $sitepress;

			$theCurrentUserId = User::getCurrentId();

			$translator_records = make( \WPML_Translator_Records::class );
			$current_language   = $currentLanguageCode ?: Languages::getCurrentCode();
			$active_languages   = $sitepress->get_active_languages();
			unset( $active_languages[ $current_language ] );
			$active_languages = array_keys( $active_languages );
			foreach ( $active_languages as $active_language ) {
				$translators           = $translator_records->get_users_with_languages( $current_language, [ $active_language ] );
				$number_of_translators = count( $translators );

				$hasOneTranslatorButHeIsNotACurrentUser = $number_of_translators === 1 && $translators[0]->ID !== $theCurrentUserId;
				if ( $hasOneTranslatorButHeIsNotACurrentUser || $number_of_translators !== 1 ) {
					return true;
				}
			}

			return false;
		};

		/** @var TranslationServices $translationService */
		$translationService = make(TranslationServices::class);

		return $translationService->isAuthorized() || ! Option::shouldTranslateEverything() && $doesNotHaveUserForEachLanguage();
	}

}
