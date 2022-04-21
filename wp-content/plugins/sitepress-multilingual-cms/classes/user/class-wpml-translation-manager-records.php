<?php

use WPML\FP\Relation;

class WPML_Translation_Manager_Records extends WPML_Translation_Roles_Records {

	/**
	 * @return string
	 */
	protected function get_capability() {
		return WPML_Manage_Translations_Role::CAPABILITY;
	}

	/**
	 * @return array
	 */
	protected function get_required_wp_roles() {

		return wpml_collect( $this->wp_roles->role_objects )
			->filter( [ $this, 'is_required_role' ] )
			->keys()
			->reject( Relation::equals( 'administrator' ) ) // Admins always have Translation Manager caps.
			->all();
	}

	/**
	 * Determine if the role can be used for a manager.
	 *
	 * @param \WP_Role $role The role definition.
	 *
	 * @return bool
	 */
	public function is_required_role( WP_Role $role ) {
		return array_key_exists( 'edit_private_posts', $role->capabilities );
	}

}
