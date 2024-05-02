<?php

namespace WPML\TM\Menu\TranslationRoles;

use WPML\FP\Obj;

class RoleValidator {

	/**
	 * Checks if a specific role is valid.
	 *
	 * @param string $roleName
	 * @return bool
	 */
	public static function isValid( $roleName ) {
		$wp_role = get_role( $roleName );
		return $wp_role instanceof \WP_Role;
	}

	/**
	 * @param string $roleName
	 *
	 * @return string|null
	 */
	public static function getTheHighestPossibleIfNotValid( $roleName ) {
		$wp_role = get_role( $roleName );
		$user    = wp_get_current_user();
		if ( \WPML_WP_Roles::get_highest_level( $wp_role->capabilities ) > \WPML_WP_Roles::get_user_max_level( $user ) ) {
			$wp_role = current( \WPML_WP_Roles::get_roles_up_to_user_level( $user ) );
			if ( ! $wp_role ) {
				return null;
			}

			$roleName = Obj::prop( 'name', $wp_role );
		}

		return $roleName;
	}
}
