<?php

namespace WPML\PB;

class TranslateLinks {

	/**
	 * @param \WPML_ST_String_Factory $stringFactory
	 * @param array $activeLanguages
	 *
	 * @return \Closure
	 */
	public static function getTranslatorForString( \WPML_ST_String_Factory $stringFactory, $activeLanguages ) {
		return function ( $string_id ) use ( $stringFactory, $activeLanguages ) {
			$string   = $stringFactory->find_by_id( $string_id );
			$statuses = \wpml_collect( $string->get_translation_statuses() )->pluck( 'language' );

			$sameStringLanguage = function ( $language ) use ( $string ) {
				return $language === $string->get_language();
			};

			$setTranslation = function ( $language ) use ( $statuses, $string ) {
				$value = $statuses->contains( $language ) ? null : $string->get_value();
				$string->set_translation( $language, $value );
			};

			\wpml_collect( $activeLanguages )->pluck( 'code' )
			                                 ->reject( $sameStringLanguage )
			                                 ->each( $setTranslation );
		};
	}

}
