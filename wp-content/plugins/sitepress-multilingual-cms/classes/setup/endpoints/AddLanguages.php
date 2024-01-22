<?php

namespace WPML\Setup\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\Element\API\Languages;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\Element\API\Entity\LanguageMapping;
use WPML\Setup\Option;

class AddLanguages implements IHandler {
	public function run( Collection $data ) {
		$languages = $data->get( 'languages' );

		$create = function ( $language ) {
			$id = Languages::add(
				$language['code'],
				$language['name'],
				$language['locale'],
				0,
				0,
				(int) $language['encode_url'],
				$language['hreflang'],
				Obj::prop('country', $language)
			);

			if ( $id ) {
				$flag = Obj::prop( 'flag', $language );
				if ( $flag ) {
					Languages::setFlag(
						$language['code'],
						Obj::propOr( '', 'name', $flag ),
						(bool) Obj::propOr( false, 'fromTemplate', $flag )
					);
				}

				/** @phpstan-ignore-next-line */
				$this->saveMapping( $language, $id );
			}

			return [ $language['code'], $id ];
		};

		$result = Either::right( Fns::map( $create, $languages ) );

		icl_cache_clear( false );

		return $result;
	}

	/**
	 * @param array $language
	 * @param int   $id
	 */
	private function saveMapping( $language, $id ) {
		$languageMapping = Obj::prop( 'mapping', $language );
		if ( $id && $languageMapping ) {
			$languageMapping = Str::split( '_', $languageMapping );

			Option::addLanguageMapping( new LanguageMapping(
					$language['code'],
					$language['name'],
					$languageMapping[0],
					Obj::prop( 1, $languageMapping ) )
			);
		}
	}
}
