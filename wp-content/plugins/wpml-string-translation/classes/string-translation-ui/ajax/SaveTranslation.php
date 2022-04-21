<?php


namespace WPML\ST\Main\Ajax;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Obj;
use WPML\ST\API\Fns as STAPI;

class SaveTranslation implements IHandler {

	public function run( Collection $data ) {
		$id          = Obj::prop( 'id', $data );
		$translation = Obj::prop( 'translation', $data );
		$lang        = Obj::prop( 'lang', $data );

		if ( $id && $translation && $lang ) {
			return Either::of( STAPI::saveTranslation( $id, $lang, $translation, ICL_TM_COMPLETE ) );
		} else {
			return Either::left( 'invalid data' );
		}
	}
}
