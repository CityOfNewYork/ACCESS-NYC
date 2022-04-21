<?php

namespace WPML\LIB\WP;

use WPML\FP\Obj;
use function WPML\FP\curryN;

class Roles {

	public static function hasCap( $cap = null, $role = null ) {
		$hasCap = function ( $cap, $role ) {
			global $wp_roles;

			return Obj::pathOr( false, [ $role, 'capabilities', $cap ], $wp_roles->roles );
		};

		return call_user_func_array( curryN( 2, $hasCap ), func_get_args() );
	}
}
