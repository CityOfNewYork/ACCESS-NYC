<?php

class WPML_WP_Roles {
	const ROLES_ADMINISTRATOR = 'administrator';
	const ROLES_EDITOR        = 'editor';
	const ROLES_CONTRIBUTOR   = 'contributor';
	const ROLES_SUBSCRIBER    = 'subscriber';

	const EDITOR_LEVEL      = 'level_7';
	const CONTRIBUTOR_LEVEL = 'level_1';
	const SUBSCRIBER_LEVEL  = 'level_0';

	/**
	 * Returns an array of roles which meet the capability level set in \WPML_WP_Roles::EDITOR_LEVEL.
	 *
	 * @return array
	 */
	public static function get_editor_roles() {
		return self::get_roles_for_level( self::EDITOR_LEVEL, self::ROLES_EDITOR );
	}

	/**
	 * Returns an array of roles which meet the capability level set in \WPML_WP_Roles::CONTRIBUTOR_LEVEL.
	 *
	 * @return array
	 */
	public static function get_contributor_roles() {
		return self::get_roles_for_level( self::CONTRIBUTOR_LEVEL, self::ROLES_CONTRIBUTOR );
	}

	/**
	 * Returns an array of roles wich meet the capability level set in \WPML_WP_Roles::SUBSCRIBER_LEVEL.
	 *
	 * @return array
	 */
	public static function get_subscriber_roles() {
		return self::get_roles_for_level( self::SUBSCRIBER_LEVEL, self::ROLES_SUBSCRIBER );
	}

	/**
	 * It returns a filtered array of roles.
	 *
	 * @param string      $level   The capability level that the role must meet.
	 * @param null|string $default The role ID to use as a default.
	 *
	 * @return array
	 */
	private static function get_roles_for_level( $level, $default = null ) {
		$roles = array();

		/**
		 * Filters the role ID to use as a default.
		 *
		 * @since 2.8.0
		 *
		 * @param string $default The role ID to use as a default.
		 * @param string $level   The capability level required for this role (@see \WPML_WP_Roles::get_roles_for_level).
		 */
		$default = apply_filters( 'wpml_role_for_level_default', $default, $level );

		$editable_roles = get_editable_roles();
		foreach ( $editable_roles as $id => $role ) {
			if ( isset( $role['capabilities'][ $level ] ) && $role['capabilities'][ $level ] ) {
				$is_default = $default && ( $default === $id );
				$roles[]    = array(
					'id'      => $id,
					'name'    => $role['name'],
					'default' => $is_default,
				);
			}
		}

		return $roles;
	}

}
