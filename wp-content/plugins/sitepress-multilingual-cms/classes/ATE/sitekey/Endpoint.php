<?php

namespace WPML\TM\ATE\Sitekey;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\Utilities\Lock;
use WPML\WP\OptionManager;
use function WPML\Container\make;

class Endpoint implements IHandler {

	public function run( Collection $data ) {

		if( function_exists( 'OTGS_Installer' ) ) {
			Lock::whileLocked( self::class, MINUTE_IN_SECONDS, [ self::class, 'sendSiteKey' ] );
		}

		return Either::of( true );
	}

	public static function sendSiteKey() {
		$sitekey = OTGS_Installer()->get_site_key( 'wpml' );
		if ( $sitekey && make( \WPML_TM_AMS_API::class )->send_sitekey( $sitekey ) ) {
			OptionManager::update( 'TM-has-run', Sync::class, true );
		}

	}
}
