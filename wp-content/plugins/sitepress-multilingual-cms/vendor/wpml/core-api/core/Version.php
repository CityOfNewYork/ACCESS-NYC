<?php

namespace WPML\API;

use WPML\Collect\Support\Traits\Macroable;
use function WPML\FP\curryN;

/**
 * Class Version
 * @package WPML\API
 *
 * @method static string firstInstallation()
 *
 * It returns the version of WPML which has been used during the first installation.
 *
 * @method static callback|bool isHigherThanInstallation( ...$version ) - Curried :: string->bool
 *
 * It compares the specified version with the version which has been used during the first installation.
 *
 * @method static string current()
 *
 * It gets the current WPML version.
 */
class Version {
	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {

		self::macro( 'firstInstallation', [ '\WPML_Installation', 'getStartVersion' ] );

		self::macro( 'isHigherThanInstallation', curryN( 1, function ( $version ) {
			return version_compare( $version, self::firstInstallation(), '>' );
		} ) );

		self::macro( 'current', function () {
			return defined( 'ICL_SITEPRESS_VERSION' ) ? ICL_SITEPRESS_VERSION : false;
		} );
	}
}

Version::init();