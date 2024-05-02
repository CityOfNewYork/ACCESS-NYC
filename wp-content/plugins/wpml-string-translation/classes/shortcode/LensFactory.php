<?php

namespace WPML\ST\Shortcode;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use function WPML\Container\make;
use function WPML\FP\gatherArgs;
use function WPML\FP\invoke;
use function WPML\FP\pipe;

class LensFactory {

	public static function createLensForJobData() {
		// $get :: array->string[]
		$get = pipe( Obj::path( [ 'fields' ] ), Lst::pluck( 'data' ) );

		// $set :: string[]->array->array
		$set = function ( $newValue, $jobData ) {
			/** @var array $newValue */
			$newValue = Obj::objOf( 'fields', Fns::map( Obj::objOf( 'data' ), $newValue ) );

			return Obj::replaceRecursive( $newValue, $jobData );
		};

		return Obj::lens( $get, $set );
	}

	public static function createLensForProxyTranslations() {
		// $getTranslations :: \WPML_TP_Translation_Collection->\WPML_TP_Translation[]
		$getTranslations = pipe( invoke( 'to_array' ), Obj::prop( 'translations' ) );

		// $get :: \WPML_TP_Translation_Collection->string[]
		$get = pipe( $getTranslations, Fns::map( Obj::prop( 'target' ) ) );

		// $set :: string[]->\WPML_TP_Translation_Collection->\WPML_TP_Translation_Collection
		$set = function ( array $translations, \WPML_TP_Translation_Collection $tpTranslations ) use ( $getTranslations ) {
			$buildNewTranslations = pipe(
				$getTranslations,
				Fns::map(
					function ( $translation, $index ) use ( $translations ) {
						return make(
							'\WPML_TP_Translation',
							[
								':field'  => $translation['field'],
								':source' => $translation['source'],
								':target' => $translations[ $index ],
							]
						);
					}
				)
			);

			return make(
				'\WPML_TP_Translation_Collection',
				[
					':translations'    => $buildNewTranslations( $tpTranslations ),
					':source_language' => $tpTranslations->get_source_language(),
					':target_language' => $tpTranslations->get_target_language(),
				]
			);
		};

		return Obj::lens( $get, $set );
	}

	public static function createLensForAssignIdInCTE() {
		// $get :: array->string[]
		$get = Fns::map( Obj::prop( 'field_data' ) );

		// $set :: string[]->array->array
		$set = function ( $updatedTranslations, $fields ) {
			$newValue = Fns::map( Obj::objOf( 'field_data' ), $updatedTranslations );

			return Obj::replaceRecursive( $newValue, $fields );
		};

		return Obj::lens( $get, $set );
	}
}
