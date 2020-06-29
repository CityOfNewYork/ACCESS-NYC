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
	 * @return array
	 */
	public static function get_roles_up_to_user_level( WP_User $user ) {
		return self::get_roles_with_max_level( self::get_user_max_level( $user ), self::ROLES_SUBSCRIBER );
	}

	/**
	 * @param  WP_User  $user
	 *
	 * @return int
	 */
	public static function get_user_max_level( WP_User $user ) {
		return self::get_highest_level( $user->get_role_caps() );
	}

	public static function get_highest_level( array $capabilities ) {
		$capabilitiesWithLevel = function ( $has, $cap ) {
			return $has && strpos( $cap, 'level_' ) === 0;
		};
		$levelToNumber = function ( $cap ) {
			return (int) substr( $cap, strlen( 'level_' ) );
		};

		return \wpml_collect( $capabilities )
			->filter( $capabilitiesWithLevel )
			->keys()
			->map( $levelToNumber )
			->sort()
			->last();
	}

	/**
	 * It returns a filtered array of roles.
	 *
	 * @param  string  $level  The capability level that the role must meet.
	 * @param  null|string  $default  The role ID to use as a default.
	 *
	 * @return array
	 */
	private static function get_roles_for_level( $level, $default = null ) {
		return \wpml_collect( get_editable_roles() )
			->filter( function ( $role ) use ( $level ) {
				return isset( $role['capabilities'][ $level ] ) && $role['capabilities'][ $level ];
			} )
			->map( self::create_build_role_entity( $level, $default ) )
			->values()
			->toArray();
	}

	private static function get_roles_with_max_level( $level, $default = null ) {
		$isRoleLowerThanLevel = function ( $role ) use ( $level ) {
			return self::get_highest_level( $role['capabilities'] ) <= $level;
		};

		return \wpml_collect( get_editable_roles() )
			->filter( $isRoleLowerThanLevel )
			->map( self::create_build_role_entity( $level, $default ) )
			->values()
			->toArray();
	}

	private static function create_build_role_entity( $level, $default = null ) {
		$is_default = self::create_is_default( $level, $default );

		return function ( $role, $id ) use ( $is_default ) {
			return [
				'id'      => $id,
				'name'    => $role['name'],
				'default' => $is_default( $id ),
			];
		};
	}

	private static function create_is_default( $level, $default = null ) {
		/**
		 * Filters the role ID to use as a default.
		 *
		 * @param  string  $default  The role ID to use as a default.
		 * @param  string  $level  The capability level required for this role (@see \WPML_WP_Roles::get_roles_for_level).
		 *
		 * @since 2.8.0
		 *
		 */
		$default    = apply_filters( 'wpml_role_for_level_default', $default, $level );
		return function ( $id ) use ( $default ) {
			return $default && ( $default === $id );
		};
	}
}
