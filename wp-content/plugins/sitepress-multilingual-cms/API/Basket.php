<?php

namespace WPML\TM\API;

use WPML\LIB\WP\User;
use WPML\Setup\Option;
use function WPML\Container\make;

class Basket {
	/**
	 * @return bool
	 */
	public static function shouldUse() {
		$doesNotHaveUserForEachLanguage = function () {
			global $sitepress;

			$translator_records = make( \WPML_Translator_Records::class );
			$current_language   = $sitepress->get_current_language();
			$active_languages   = $sitepress->get_active_languages();
			unset( $active_languages[ $current_language ] );
			$active_languages = array_keys( $active_languages );
			foreach ( $active_languages as $active_language ) {
				$translators           = $translator_records->get_users_with_languages( $current_language, [ $active_language ] );
				$number_of_translators = count( $translators );

				if ( 1 !== $number_of_translators ) {
					return true;
				}
			}
			return false;
		};

		return ! \WPML_TM_ATE_Status::is_enabled_and_activated()
			|| \TranslationProxy::is_current_service_active_and_authenticated()
			|| ! Option::shouldTranslateEverything() && $doesNotHaveUserForEachLanguage();
	}

}
