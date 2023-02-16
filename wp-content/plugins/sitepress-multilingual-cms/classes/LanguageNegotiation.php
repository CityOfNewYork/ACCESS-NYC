<?php

namespace WPML\Core;


use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Obj;
use function WPML\FP\curryN;
use function WPML\FP\partial;

/**
 * Class LanguageNegotiation
 * @package WPML\Core
 *
 * @method static callable|void saveMode( ...$mode ) - int|string->void
 *
 * @method static int getMode()
 *
 * @method static string getModeAsString( $mode = null )
 *
 * @method static callable|void saveDomains( ...$domains ) - array->void
 *
 * @method static array getDomains()
 */
class LanguageNegotiation {
	use Macroable;

	const DIRECTORY = 1;
	const DOMAIN = 2;
	const PARAMETER = 3;

	const DIRECTORY_STRING = 'directory';
	const DOMAIN_STRING = 'domain';
	const PARAMETER_STRING = 'parameter';

	private static $modeMap = [
		self::DIRECTORY_STRING => self::DIRECTORY,
		self::DOMAIN_STRING    => self::DOMAIN,
		self::PARAMETER_STRING => self::PARAMETER,
	];

	/**
	 * @ignore
	 */
	public static function init() {
		global $sitepress;

		self::macro( 'saveMode', curryN( 1, function ( $mode ) use ( $sitepress ) {
			$mode = is_numeric( $mode )
				? (int) $mode
				: Obj::propOr( self::PARAMETER, $mode, self::$modeMap );

			$sitepress->set_setting( 'language_negotiation_type', $mode, true );
		} ) );

		self::macro( 'getMode', partial( [ $sitepress, 'get_setting' ], 'language_negotiation_type' ) );

		self::macro( 'getModeAsString', function ( $mode = null ) {
			return \wpml_collect( self::$modeMap )->flip()->get( $mode ?: self::getMode(), self::DIRECTORY_STRING );
		} );

		self::macro( 'saveDomains', curryN( 1, function ( $domains ) use ( $sitepress ) {
			$sitepress->set_setting( 'language_domains', $domains, true );
		} ) );

		self::macro( 'getDomains', partial( [ $sitepress, 'get_setting' ], 'language_domains' ) );

	}
}

LanguageNegotiation::init();
