<?php

namespace WPML\Element\API;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Fns;
use WPML\FP\Maybe;
use function WPML\FP\curryN;

/**
 * Class Translations
 * @package WPML\Element\API
 * @method static callable|int setLanguage( ...$el_id, ...$el_type, ...$trid, ...$language_code, ...$src_language_code, ...$check_duplicates )
 *
 * - Curried :: int → string → int|null → string → string → string|null → bool → bool|int|null|string
 *
 *          Wrapper function for SitePress::set_element_language_details
 *
 * - int         $el_id the element's ID (for terms we use the `term_taxonomy_id`)
 * - string      $el_type
 * - int         $trid
 * - string      $language_code
 * - null|string $src_language_code
 * - bool        $check_duplicates
 *
 * returns bool|int|null|string
 *
 * @method static callable|int setAsSource( ...$el_id, ...$el_type, ...$language_code )
 * @method static callable|int setAsTranslationOf( ...$el_id, ...$el_type, ...$translated_id, ...$language_code )
 * @method static callable|array get( ...$el_id, ...$el_type )
 * @method static callable|array getIfOriginal( ...$el_id, ...$el_type )
 * @method static callable|bool isOriginal( ...$el_id, ...$translations )
 */
class Translations {

	use Macroable;

	/**
	 * @ignore
	 */
	public static function init() {
		global $sitepress;

		self::macro( 'setLanguage', curryN( 6, [ $sitepress, 'set_element_language_details' ] ) );

		self::macro( 'setAsSource', self::setLanguage( Fns::__, Fns::__, null, Fns::__, null, true ) );

		self::macro( 'setAsTranslationOf', curryN( 4,
			function ( $el_id, $el_type, $translated_id, $language_code ) use ( $sitepress ) {
				$trid = $sitepress->get_element_trid( $el_id, $el_type );
				self::setLanguage( $translated_id, $el_type, $trid, $language_code, null, true );
			} ) );

		self::macro( 'get', curryN( 2, function ( $el_id, $el_type ) use ( $sitepress ) {
			$trid = $sitepress->get_element_trid( $el_id, $el_type );

			return $sitepress->get_element_translations( $trid, $el_type );
		} ) );

		self::macro( 'getIfOriginal', curryN( 2, function ( $el_id, $el_type ) {
			return Maybe::of( self::get( $el_id, $el_type ) )
			            ->filter( self::isOriginal( $el_id ) )
			            ->getOrElse( [] );
		} ) );

		self::macro( 'isOriginal', curryN( 2, function ( $id, $translations ) {
			$isElementOriginal = curryN( 3, function ( $id, $state, $element ) {
				return $state || ( $element->original && (int) $element->element_id === ( int ) $id );
			} );

			return Fns::reduce( $isElementOriginal( $id ), false, $translations );
		} ) );

	}
}

Translations::init();
