<?php

namespace WPML\LIB\WP;

use function WPML\FP\curryN;
use function WPML\FP\partialRight;
use WPML\FP\Fns;

class User {

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

}
