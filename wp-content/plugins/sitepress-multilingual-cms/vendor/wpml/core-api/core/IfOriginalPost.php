<?php

namespace WPML\Element\API;

use WPML\FP\Fns;
use WPML\FP\Obj;
use function WPML\FP\curryN;
use function WPML\FP\pipe;

class IfOriginalPost {

	/**
	 * Gets the element details for the translations of the given post id.
	 * Returns an empty array if the id is not an original post.
	 *
	 * element details structure:
	 * ```php
	 * (object) [
	 *  'original' => false,            // bool True if the element is the original, false if a translation
	 *  'element_id' => 123,            // int The element id
	 *  'source_language_code' => 'en', // string The source language code
	 *  'language_code' => 'de',        // string The language of the element
	 *  'trid' => 456,                  // int The translation id that links translations to source.
	 * ]
	 * ```
	 *
	 * @param int $id The post id. Optional. If missing then returns a callable waiting for the id.
	 *
	 * @return \WPML\Collect\Support\Collection<mixed>|callable
	 */
	public static function getTranslations( $id = null ) {
		$get = pipe( PostTranslations::getIfOriginal(), Fns::reject( Obj::prop( 'original' ) ), 'wpml_collect' );

		return call_user_func_array( curryN( 1, $get ), func_get_args() );
	}

	/**
	 * Get the element ids for the translations of the given post id.
	 * Returns an empty array if the id is not an original post.
	 *
	 * @param int $id The post id. Optional. If missing then returns a callable waiting for the id.
	 *
	 * @return \WPML\Collect\Support\Collection<mixed>|callable
	 */
	public static function getTranslationIds( $id = null ) {
		$get = pipe( self::getTranslations(), Fns::map( Obj::prop( 'element_id' ) ) );

		return call_user_func_array( curryN( 1, $get ), func_get_args() );
	}
}

