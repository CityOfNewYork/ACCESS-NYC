<?php

namespace WPML\ST\Shortcode;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Str;
use function WPML\FP\curryN;
use function WPML\FP\partial;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class Xliff {

	const SHORTCODE_PATTERN = '/\[wpml-string.*?\[\/wpml-string\]/';

	/**
	 * It appends "id" attribute to [wpml-string] shortcode.
	 *
	 * @param  callable  $getStringRowByItsDomainAndValue
	 * @param  string  $fieldData
	 *
	 * @return string
	 * @since 3.1.0
	 */
	public static function appendId( callable $getStringRowByItsDomainAndValue, $fieldData ) {
		// $getStringId :: [ domain, value ] → id
		$getStringId = spreadArgs( pipe( $getStringRowByItsDomainAndValue, Obj::prop( 'id' ) ) );
		// appendStringId :: [ _, domain, value ] → [ _, domain, value, id ]
		$appendStringId = self::appendToData( pipe( Lst::takeLast( 2 ), $getStringId ) );

		// $getShortcode :: [shortcode, domain, id] -> shortcode
		$getShortcode = Lst::nth( 0 );
		// $getShortcode :: [shortcode, domain, id] -> id
		$getId        = Lst::last();

		// $extractDomain :: string -> string
		$extractDomain = self::firstMatchingGroup( '/context="(.*?)"/', 'wpml-shortcode' );

		// $forceRegisterShortcodeString :: string -> void
		$forceRegisterShortcodeString = pipe( $getShortcode, 'do_shortcode' );

		// $newShortcode :: [shortcode, domain, id] -> string
		$newShortcode          = function ( $data ) use ( $getId, $getShortcode ) {
			$pattern = '/\[wpml-string(.*)\]/';
			$replace = '[wpml-string id="' . $getId( $data ) . '"${1}]';

			return preg_replace( $pattern, $replace, $getShortcode( $data ) );
		};
		// $updateSingleShortcode :: [shortcode, domain, id] -> [shortcode, newShortcode]
		$updateSingleShortcode = Fns::converge( Lst::makePair(), [ $getShortcode, $newShortcode ] );

		// $updateFieldData :: string, [shortcode, newShortcode] -> string
		$updateFieldData = function ( $fieldData, $shortCodePairs ) {
			list( $shortcode, $newShortcode ) = $shortCodePairs;

			return str_replace( $shortcode, $newShortcode, $fieldData );
		};

		return \wpml_collect( Str::matchAll( self::SHORTCODE_PATTERN, $fieldData ) )
			->map( self::appendToData( pipe( $getShortcode, $extractDomain ) ) )
			->map( self::appendToData( pipe( $getShortcode, self::extractInnerText() ) ) )
			->each( $forceRegisterShortcodeString )
			->map( $appendStringId )
			->filter( $getId )
			->map( $updateSingleShortcode )
			->reduce( $updateFieldData, $fieldData );
	}

	/**
	 * It detects all [wpml-string] shortcodes in $jobData and registers string translations
	 *
	 * @param  array  $jobData
	 * @param  callable  $getJobTargetLanguage
	 *
	 * @return array
	 */
	public static function registerStringTranslation( array $jobData, callable $getJobTargetLanguage ) {
		$targetLanguage = $getJobTargetLanguage( $jobData['job_id'] );
		if ( ! $targetLanguage ) {
			return $jobData;
		}

		$registerStringTranslation = curryN( 4, 'icl_add_string_translation' );
		// $registerStringTranslation :: stringId, translationValue -> translationId
		$registerStringTranslation = $registerStringTranslation(
			Fns::__,
			$targetLanguage,
			Fns::__,
			ICL_STRING_TRANSLATION_COMPLETE
		);

		// $getStringIdAndTranslations :: [shortcode, id, translation] -> [id, translation]
		$getStringIdAndTranslations = Lst::drop( 1 );

		// $registerTranslationOfSingleString :: [shortcode, id, translation] -> void
		$registerTranslationOfSingleString = pipe(
			$getStringIdAndTranslations,
			spreadArgs( $registerStringTranslation )
		);

		// $registerStringsFromFieldData :: string -> void
		$registerStringsFromFieldData = pipe(
			self::findShortcodesInJobData(),
			Fns::each( $registerTranslationOfSingleString )
		);

		$data = Fns::map( Obj::prop( 'data' ), Obj::path( [ 'fields' ], $jobData ) );
		Fns::each( $registerStringsFromFieldData, $data );

		return $jobData;
	}

	/**
	 * It detects all [wpml-string] shortcodes in $jobData and
	 *  - removes "id" attribute
	 *  - replaces translated inner text by its original value
	 *
	 * @param  callable  $getStringById
	 * @param  array  $jobData
	 *
	 * @return array
	 */
	public static function restoreOriginalShortcodes( callable $getStringById, array $jobData ) {
		// $getOriginalStringValue :: int -> string
		$getOriginalStringValue = pipe( $getStringById, Obj::prop( 'value' ) );
		// $restoreSingleShortcode :: string, [string, id] -> string
		$restoreSingleShortcode = function ( $fieldData, $shortcodeMatches ) use ( $getOriginalStringValue ) {
			list( , $stringId ) = $shortcodeMatches;

			$pattern     = '/\[wpml-string id="' . $stringId . '"(.*?)\](.*?)\[\/wpml-string\]/';
			$replacement = '[wpml-string${1}]' . $getOriginalStringValue( $stringId ) . '[/wpml-string]';

			return preg_replace( $pattern, $replacement, $fieldData );
		};

		// $updateFieldData :: string -> string
		$updateFieldData = Fns::converge( Fns::reduce( $restoreSingleShortcode ), [
			Fns::identity(),
			self::findShortcodesInJobData(),
		] );

		return Obj::evolve( [ 'fields' => Fns::map( Obj::evolve( [ 'data' => $updateFieldData ] ) ) ], $jobData );
	}

	// findShortcodesInJobData :: void → ( string → [shortcode, id, translation] )
	private static function findShortcodesInJobData() {
		return function ( $str ) {
			$getId     = Lst::nth( 1 );
			$extractId = self::firstMatchingGroup( '/id="(.*?)"/' );

			return \wpml_collect( Str::matchAll( self::SHORTCODE_PATTERN, $str ) )
				->map( self::appendToData( pipe( Lst::nth( 0 ), $extractId ) ) )
				->map( self::appendToData( pipe( Lst::nth( 0 ), self::extractInnerText() ) ) )
				->filter( $getId );
		};
	}

	// appendToData :: callable -> ( array -> array )
	private static function appendToData( callable $fn ) {
		return Fns::converge( Lst::append(), [ $fn, Fns::identity() ] );
	}

	// firstMatchingGroup :: string, string|null -> ( string -> string )
	private static function firstMatchingGroup( $pattern, $fallback = null ) {
		return function ( $str ) use ( $pattern, $fallback ) {
			return Lst::nth( 1, Str::match( $pattern, $str ) ) ?: $fallback;
		};
	}

	// extractInnerText :: void -> ( string -> string )
	private static function extractInnerText() {
		return self::firstMatchingGroup( '/\](.*)\[/' );
	}
}