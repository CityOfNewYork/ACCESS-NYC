<?php

namespace WPML;

use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\User;
use WPML\LIB\WP\Roles as WPRoles;
use function WPML\FP\spreadArgs;

class Roles implements \IWPML_Backend_Action, \IWPML_AJAX_Action {

	public function add_hooks() {
		Hooks::onAction( 'set_user_role', 10, 3 )->then( spreadArgs( [ self::class, 'remove_caps' ] ) );
	}

	public static function remove_caps( $userId, $role, $oldRoles ) {
		if ( ! WPRoles::hasCap( 'manage_options', $role ) ) {
			$user = User::get( $userId );

			wpml_collect( DefaultCapabilities::get() )
				->keys()
				->map( [ $user, 'remove_cap' ] );
		}
	}
}
