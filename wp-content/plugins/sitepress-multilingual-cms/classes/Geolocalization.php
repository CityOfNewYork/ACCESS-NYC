<?php

namespace WPML\TM;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Json;
use WPML\FP\Logic;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use function WPML\FP\curryN;

/**
 * @method static callable getCountryByIp( ...$httpPost, ...$ip ) - Curried :: callable->string->array|null
 */
class Geolocalization {
	use Macroable;

	public static function init() {
		self::macro(
			'getCountryByIp',
			curryN(
				2,
				function ( $httpPost, $ip ) {
					$ip  = defined( 'WPML_TM_Geolocalization_IP' ) ? WPML_TM_Geolocalization_IP : $ip;
					$url = defined( 'OTGS_INSTALLER_WPML_API_URL' ) ? OTGS_INSTALLER_WPML_API_URL : 'https://api.wpml.org';

					$formatRequest = function ( $ip ) {
						return [
							'body' => [
								'action' => 'geolocalization',
								'ip'     => $ip,
							],
						];
					};

					return Maybe::of( $ip )
							->map( $formatRequest )
							->chain( $httpPost( $url ) )
							->map( Obj::prop( 'body' ) )
							->map( Json::toArray() )
							->map( Obj::prop( 'data' ) )
							->filter( Logic::allPass( [ Obj::prop( 'code' ), Obj::prop( 'name' ) ] ) )
							->map( Obj::pick( [ 'code', 'name' ] ) )
							->getOrElse( null );

				}
			)
		);
	}
}

Geolocalization::init();
