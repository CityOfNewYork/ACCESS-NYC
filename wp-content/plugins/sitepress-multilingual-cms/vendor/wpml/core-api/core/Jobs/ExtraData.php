<?php

namespace WPML\Jobs;

use WPML\FP\Curryable;

/**
 * Class ExtraData
 * @package WPML\Jobs
 *
 * @method static callable|string decode( ...$extradata ): Curried :: string->array
 * @method static callable|string encode( ...$extradata ): Curried :: array->string
 */
class ExtraData {

	use Curryable;

	public static function init() {

		self::curryN( 'decode', 1, function ( $extradata ) {
			return json_decode( str_replace( '&quot;', '"', $extradata ), true );
		} );

		self::curryN( 'encode', 1, function ( $extradata ) {
			return str_replace( '"', '&quot;', wp_json_encode( $extradata ) );
		} );

	}

}

ExtraData::init();

