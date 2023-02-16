<?php

namespace WPML\TM\API;

use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\LIB\WP\User;

class Translators {
	/**
	 * @return \WPML_Translator
	 */
	public static function getCurrent() {
		$translator = wpml_load_core_tm()->get_current_translator();

		if (
			empty( $translator->language_pairs )
		     && User::getCurrent()->has_cap( \WPML_Manage_Translations_Role::CAPABILITY )
		) {
			return Obj::assoc( 'language_pairs', \WPML_All_Language_Pairs::get(), $translator );
		}

		return Obj::over(
			Obj::lensProp( 'language_pairs' ),
			Fns::map( Obj::keys() ),
			$translator
		);
	}
}
