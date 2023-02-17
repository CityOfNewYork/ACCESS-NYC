<?php

namespace WPML\PB\Gutenberg\Widgets\Block;

use WPML\Element\API\Languages;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\ST\TranslationFile\Manager;
use function WPML\Container\make;
use function WPML\FP\curryN;

class Strings {

	const PACKAGE_KIND = 'Block';
	const PACKAGE_KIND_SLUG = 'block';
	const PACKAGE_NAME = 'widget';
	const PACKAGE_TITLE = 'Widget';

	const DOMAIN = self::PACKAGE_KIND_SLUG . '-' . self::PACKAGE_NAME;

	/**
	 * @param string $locale
	 *
	 * @return array
	 * @throws \WPML\Auryn\InjectionException
	 */
	public static function fromMo( $locale ) {
		$langCode = Languages::localeToCode( $locale );

		$encode = curryN( 2, function ( $langCode, $value ) {
			return [
				$langCode => [
					'value'  => $value,
					'status' => ICL_STRING_TRANSLATION_COMPLETE,
				],
			];
		} );

		return wpml_collect( self::loadStringsFromMOFile( self::DOMAIN, $locale ) )
			->map( Obj::path( [ 'translations', 0 ] ) )
			->filter()
			->map( $encode( $langCode ) )
			->toArray();
	}

	public static function loadStringsFromMOFile( $domain, $locale ) {
		return Maybe::of( Manager::getSubdir() . '/' . $domain . "-$locale.mo" )
		            ->filter( 'file_exists' )
		            ->map( function ( $file ) {
			            $mo = make( \MO::class );
			            $mo->import_from_file( $file );

			            return $mo;
		            } )
		            ->map( Obj::prop( 'entries' ) )
		            ->getOrElse( [] );
	}

	public static function createPackage() {
		return [
			'kind'      => self::PACKAGE_KIND,
			'kind_slug' => self::PACKAGE_KIND_SLUG,
			'name'      => self::PACKAGE_NAME,
			'title'     => self::PACKAGE_TITLE,
			'post_id'   => null,
		];
	}
}
