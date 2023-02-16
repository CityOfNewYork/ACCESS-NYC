<?php


namespace WPML\ST\Basket;

use WPML\FP\Obj;

class Status {

	public static function add( array $translations, $languages ) {
		$statusProvider = [ 'TranslationProxy_Basket', 'is_in_basket' ];
		if ( is_callable( $statusProvider ) ) {
			$translations = self::addWithProvider( $translations, $languages, $statusProvider );
		}

		return $translations;
	}

	private static function addWithProvider( array $translations, $languages, callable $statusProvider ) {
		foreach ( $translations as $id => $string ) {
			foreach ( Obj::propOr( [], 'translations', $string ) as $lang => $data ) {
				$translations[ $id ]['translations'][ $lang ]['in_basket'] = $statusProvider( $id, $string['string_language'], $lang, 'string' );
			}
			foreach ( $languages as $lang ) {
				if (
					$lang !== $string['string_language']
					&& ! isset( $translations[ $id ]['translations'][ $lang ] )
					&& $statusProvider( $id, $string['string_language'], $lang, 'string' )
				) {
					$translations[ $id ]['translations'][ $lang ] = [
						'id'        => 0,
						'language'  => $lang,
						'in_basket' => true,
					];
				}
			}
		}

		return $translations;
	}
}
