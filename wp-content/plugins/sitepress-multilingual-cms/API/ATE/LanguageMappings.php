<?php

namespace WPML\TM\API\ATE;

use WPML\Element\API\Languages;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Wrapper;
use WPML\LIB\WP\Option;
use WPML\Element\API\Entity\LanguageMapping;
use WPML\TM\ATE\API\CacheStorage\StaticVariable;
use WPML\TM\ATE\API\CachedATEAPI;
use function WPML\Container\make;
use function WPML\FP\curryN;
use function WPML\FP\invoke;
use function WPML\FP\pipe;

class LanguageMappings {
	const IGNORE_MAPPING_OPTION = 'wpml-languages-ignore-mapping';
	const IGNORE_MAPPING_ID = - 1;

	public static function withCanBeTranslatedAutomatically( $languages = null ) {
		$fn = curryN( 1, function ( $languages ) {
			if ( ! is_object( $languages ) && ! is_array( $languages ) ) {
				return $languages;
			}
			$ateAPI             = static::getATEAPI();
			$targetCodes        = Lst::pluck( 'code', Obj::values( $languages ) );
			$supportedLanguages = $ateAPI->get_languages_supported_by_automatic_translations( $targetCodes )->getOrElse( [] );

			$areThereAnySupportedLanguages = Lst::find( Logic::isNotNull(), $supportedLanguages );
			$isSupportedCode               = pipe( Obj::prop( Fns::__, $supportedLanguages ), Logic::isNotNull() );
			$isNotMarkedAsDontMap          = Logic::complement( Lst::includes( Fns::__, Option::getOr( self::IGNORE_MAPPING_OPTION, [] ) ) );

			$isDefaultCode          = Relation::equals( Languages::getDefaultCode() );
			$isSupportedByAnyEngine = pipe(
				pipe( [ $ateAPI, 'get_language_details' ], invoke( 'getOrElse' )->with( [] ) ),
				Logic::anyPass( [ Obj::prop( 'ms_api_iso' ), Obj::prop( 'google_api_iso' ), Obj::prop( 'deepl_api_iso' ) ] )
			);
			$isDefaultLangSupported = Logic::anyPass( [ Fns::always( $areThereAnySupportedLanguages ), $isSupportedByAnyEngine ] );

			$isSupported = pipe( Obj::prop( 'code' ), Logic::both(
				$isNotMarkedAsDontMap,
				Logic::ifElse( $isDefaultCode, $isDefaultLangSupported, $isSupportedCode )
			) );

			return Fns::map( Obj::addProp( 'can_be_translated_automatically', $isSupported ), $languages );
		} );

		return call_user_func_array( $fn, func_get_args() );
	}

	public static function isCodeEligibleForAutomaticTranslations( $languageCode = null ) {
		$fn = Lst::includes( Fns::__, static::geCodesEligibleForAutomaticTranslations() );

		return call_user_func_array( $fn, func_get_args() );
	}

	/**
	 * @return LanguageMapping[] $mappings
	 */
	public static function get() {
		$ignoredMappings = Fns::map( function ( $code ) {
			return new LanguageMapping( $code, '', self::IGNORE_MAPPING_ID );
		}, Option::getOr( self::IGNORE_MAPPING_OPTION, [] ) );

		$mappingInATE = Fns::map( function ( $record ) {
			return new LanguageMapping(
				Obj::prop( 'source_code', $record ),
				Obj::path( [ 'source_language', 'name' ], $record ),
				Obj::path( [ 'target_language', 'id' ], $record ),
				Obj::prop( 'target_code', $record )
			);
		}, static::getATEAPI()->get_language_mapping()->getOrElse( [] ) );

		return Lst::concat( $ignoredMappings, $mappingInATE );
	}

	public static function withMapping( $languages = null ) {
		$fn = curryN( 1, function ( $languages ) {
			$mapping           = self::get();
			$findMappingByCode = function ( $language ) use ( $mapping ) {
				return Lst::find( invoke( 'matches' )->with( Obj::prop( 'code', $language ) ), $mapping );
			};

			return Fns::map( Obj::addProp( 'mapping', $findMappingByCode ), $languages );
		} );

		return call_user_func_array( $fn, func_get_args() );
	}

	/**
	 * @return array
	 */
	public static function getAvailable() {
		$mapping = static::getATEAPI()->get_available_languages();

		return Relation::sortWith( [ Fns::ascend( Obj::prop( 'name'  ) ) ], $mapping );
	}


	/**
	 * @param LanguageMapping[] $mappings
	 *
	 * @return Either
	 */
	public static function saveMapping( array $mappings ) {
		list( $ignoredMapping, $mappingSet ) = \wpml_collect( $mappings )->partition( Relation::propEq( 'targetId', self::IGNORE_MAPPING_ID ) );

		$ignoredCodes = $ignoredMapping->pluck( 'sourceCode' )->toArray();
		Option::update( self::IGNORE_MAPPING_OPTION, $ignoredCodes );

		$ateAPI = static::getATEAPI();
		if ( count( $ignoredCodes ) ) {
			$ateAPI->get_language_mapping()
			       ->map( Fns::filter( pipe( Obj::prop( 'source_code' ), Lst::includes( Fns::__, $ignoredCodes ) ) ) )
			       ->map( Lst::pluck( 'id' ) )
			       ->filter( Logic::complement( Logic::isEmpty() ) )
			       ->map( [ $ateAPI, 'remove_language_mapping' ] );
		}

		return $ateAPI->create_language_mapping( $mappingSet->values()->toArray() );
	}

	/**
	 * @return array
	 */
	public static function getLanguagesEligibleForAutomaticTranslations() {
		return Wrapper::of( Languages::getSecondaries() )
		              ->map( static::withCanBeTranslatedAutomatically() )
		              ->map( Fns::filter( Obj::prop( 'can_be_translated_automatically' ) ) )
		              ->get();
	}

	/**
	 * @return string[]
	 */
	public static function geCodesEligibleForAutomaticTranslations() {
		return Lst::pluck( 'code', static::getLanguagesEligibleForAutomaticTranslations() );
	}

	public static function hasTheSameMappingAsDefaultLang( $language = null ) {
		$fn = curryN( 1, function ( $language ) {
			$defaultLanguage = Lst::last( static::withMapping( [ Languages::getDefault() ] ) );
			if ( ! is_object( $defaultLanguage ) && ! is_array( $defaultLanguage ) ) {
				return false;
			}
			$defaultLanguageMappingTargetCode = Obj::pathOr( Obj::prop( 'code', $defaultLanguage ), [ 'mapping', 'targetCode' ], $defaultLanguage );

			return Obj::pathOr( null, [ 'mapping', 'targetCode' ], $language ) === $defaultLanguageMappingTargetCode;
		} );

		try {
			$hasMapping = call_user_func_array( $fn, func_get_args() );
		} catch ( \InvalidArgumentException $e ) {
			$hasMapping = false;
		}

		return $hasMapping;
	}

	/**
	 * @return CachedATEAPI
	 */
	protected static function getATEAPI() {
		return new CachedATEAPI( make( \WPML_TM_ATE_API::class ), StaticVariable::getInstance() );
	}
}
