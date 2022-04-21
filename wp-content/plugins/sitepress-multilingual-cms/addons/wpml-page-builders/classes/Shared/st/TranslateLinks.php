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
			$string = $stringFactory->find_by_id( $string_id );

			$sameStringLanguage = function ( $language ) use ( $string ) {
				return $language === $string->get_language();
			};

			$setTranslation = function ( $language ) use ( $string ) {
				$string->set_translation( $language, $string->get_value() );
			};

			\wpml_collect( $activeLanguages )->pluck( 'code' )
			                                 ->reject( $sameStringLanguage )
			                                 ->each( $setTranslation );
		};
	}

}
