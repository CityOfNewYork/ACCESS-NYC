<?php

namespace WPML\LIB\WP;

use function WPML\FP\curryN;
use function WPML\FP\partialRight;
use WPML\FP\Fns;

class User {
	const CAP_MANAGE_TRANSLATIONS = 'manage_translations';
	const CAP_MANAGE_OPTIONS = 'manage_options';
	const CAP_ADMINISTRATOR = 'administrator';
	const CAP_TRANSLATE = 'translate';
	const CAP_MANAGE_TRANSLATION_MANAGEMENT = 'wpml_manage_translation_management';
	const CAP_PUBLISH_PAGES = 'publish_pages';
	const CAP_PUBLISH_POSTS = 'publish_posts';
	const CAP_EDIT_OTHERS_PAGES = 'edit_others_pages';
	const CAP_EDIT_OTHERS_POSTS = 'edit_others_posts';
	const CAP_EDIT_POSTS = 'edit_posts';
	const CAP_EDIT_PAGES = 'edit_pages';
	const ROLE_EDITOR_MINIMUM_CAPS = [
		self::CAP_EDIT_OTHERS_POSTS,
		self::CAP_PUBLISH_PAGES,
		self::CAP_PUBLISH_POSTS,
		self::CAP_EDIT_PAGES,
		self::CAP_EDIT_POSTS,
	];

	/** @var array Calling user_can() is a very memory heavy function. */
	private static $userCanCache = [];

	/**
	 * @param int|WP_User $user
	 * @param string      $capability
	 *
	 * @return bool
	 */
	public static function userCan( $user, $capability ) {
		if ( $user instanceof \WP_User ) {
			$user = $user->ID;
		}

		if ( ! isset( self::$userCanCache[ $user ] ) ) {
			self::$userCanCache[ $user ] = [];
		}

		if ( ! isset( self::$userCanCache[ $user ][ $capability ] ) ) {
			self::$userCanCache[ $user ][ $capability ] = user_can( $user, $capability );
		}

		return self::$userCanCache[ $user ][ $capability ];
	}

	/**
	 * @param string $capability
	 *
	 * @return bool
	 */
	public static function currentUserCan( $capability ) {
		return self::userCan( self::getCurrentId(), $capability );
	}

	/**
	 * @return int
	 */
	public static function getCurrentId() {
		return get_current_user_id();
	}

	/**
	 * @return \WP_User|null
	 */
	public static function getCurrent() {
		return wp_get_current_user();
	}

	/**
	 * Curried function to update the user meta.
	 *
	 * @param int    $userId
	 * @param string $metaKey
	 * @param mixed  $metaValue
	 *
	 * @return callable|int|bool
	 */
	public static function updateMeta( $userId = null, $metaKey = null, $metaValue = null ) {
		return call_user_func_array( curryN( 3, 'update_user_meta' ), func_get_args() );
	}

	/**
	 * Curried function to get the user meta
	 *
	 * @param int    $userId
	 * @param string $metaKey
	 *
	 * @return callable|mixed
	 */
	public static function getMetaSingle( $userId = null, $metaKey = null ) {
		return call_user_func_array( curryN( 2, partialRight( 'get_user_meta', true ) ), func_get_args() );
	}

	/**
	 * Curried function to get the user meta
	 *
	 * @param int    $userId
	 * @param string $metaKey
	 *
	 * @return callable|bool
	 */
	public static function deleteMeta( $userId = null, $metaKey = null ) {
		return call_user_func_array( curryN( 2, 'delete_user_meta' ), func_get_args() );
	}

	/**
	 * @param int|null $userId
	 *
	 * @return callable|\WP_User
	 */
	public static function get( $userId = null ) {
		return call_user_func_array( curryN( 1, function ( $userId ) {
			return new \WP_User( $userId );
		} ), func_get_args() );
	}

	/**
	 * @param array|null $data
	 *
	 * @return callable|int|\WP_Error
	 */
	public static function insert( $data = null ) {
		return call_user_func_array( curryN( 1, 'wp_insert_user' ), func_get_args() );
	}

	/**
	 * @param int|null $userId
	 *
	 * @return callable|int
	 */
	public static function notifyNew( $userId = null ) {
		return call_user_func_array( curryN( 1, Fns::tap( 'wp_send_new_user_notifications' ) ), func_get_args() );
	}

	/**
	 * Add the avatar to a user.
	 *
	 * @param object|\WP_User $user
	 *
	 * @return callable|object
	 */
	public static function withAvatar( $user = null ) {
		$withAvatar = function ( $user ) {
			$user->avatar    = get_avatar( $user->ID );
			$user->avatarUrl = get_avatar_url( $user->ID );

			return $user;
		};

		return call_user_func_array( curryN( 1, $withAvatar ), func_get_args() );
	}

	/**
	 * Add the edit link to a user.
	 *
	 * @param object|\WP_User $user
	 *
	 * @return callable|object
	 */
	public static function withEditLink( $user = null ) {
		$withEditLink = function ( $user ) {
			$user->editLink = esc_url(
				add_query_arg(
					'wp_http_referer',
					urlencode( esc_url( stripslashes( $_SERVER['REQUEST_URI'] ) ) ),
					"user-edit.php?user_id={$user->ID}"
				)
			);

			return $user;
		};

		return call_user_func_array( curryN( 1, $withEditLink ), func_get_args() );
	}

	/**
	 * Checks if the given user has the requested capability.
	 * The current user is used if no user is defined.
	 *
	 * @param string $capability Capability to check for.
	 * @param ?\WP_User $user User to check. Using current user if not defined.
	 */
	public static function hasCap( $capabilitiy, \WP_User $user = null ) {
		$user = $user ?: self::getCurrent();
		return $user->has_cap( $capabilitiy );
	}

	/**
	 * Check if user can manage translations (Translation Manager).
	 * Alias for self::hasCap( User::CAP_MANAGE_TRANSLATIONS ).
	 *
	 * @param ?\WP_User $user User to check. Using current user if not defined.
	 */
	public static function canManageTranslations( \WP_User $user = null ) {
		return self::hasCap( self::CAP_MANAGE_TRANSLATIONS, $user ) || self::isAdministrator( $user );
	}

	/**
	 * Check if user can manage options (Administrator).
	 * Alias for self::hasCap( User::CAP_MANAGE_OPTIONS ).
	 *
	 * @param ?\WP_User $user User to check. Using current user if not defined.
	 */
	public static function canManageOptions( \WP_User $user = null ) {
		return self::hasCap( self::CAP_MANAGE_OPTIONS, $user );
	}

	/**
	 * @param \WP_User|null $user User to check. Using current user if not defined.
	 *
	 * @return bool
	 */
	public static function isAdministrator( \WP_User $user = null ) {
		return self::hasCap( self::CAP_ADMINISTRATOR, $user );
	}

	/**
	 * @param \WP_User|null $user User to check. Using current user if not defined.
	 *
	 * @return bool
	 */
	public static function isEditor( \WP_User $user = null ) {
		foreach ( static::ROLE_EDITOR_MINIMUM_CAPS as $cap ) {
			if ( ! self::hasCap( $cap, $user ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param \WP_User|null $user User to check. Using current user if not defined.
	 *
	 * @return bool
	 */
	public static function isTranslator( \WP_User $user = null ) {
		return self::hasCap( self::CAP_TRANSLATE, $user );
	}
}
