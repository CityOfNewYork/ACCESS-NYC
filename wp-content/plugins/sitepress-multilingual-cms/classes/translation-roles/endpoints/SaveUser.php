<?php

namespace WPML\TranslationRoles;

use WPML\Collect\Support\Collection;
use WPML\Ajax\IHandler;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Left;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Right;
use WPML\LIB\WP\User;
use WPML\TM\Menu\TranslationRoles\RoleValidator;
use WPML\LIB\WP\WordPress;
use function WPML\FP\invoke;
use function WPML\FP\partial;

abstract class SaveUser implements IHandler {

	/**
	 * @param Collection $data
	 *
	 * @return Left|Right
	 */
	protected static function getUser( Collection $data ) {
		$createNew = partial( [ self::class, 'createNewWpUser' ],  $data );

		return Either::fromNullable( Obj::path( [ 'user', 'ID' ], $data ) )
		             ->map( User::get() )
		             ->bichain( $createNew, Either::of() );
	}

	/**
	 * @param Collection $data
	 *
	 * @return Left|Right
	 */
	public static function createNewWpUser( Collection $data ) {
		$get       = Obj::prop( Fns::__, $data->get( 'user' ) );
		$firstName = $get( 'first' );
		$lastName  = $get( 'last' );
		$email     = filter_var( $get( 'email' ), FILTER_SANITIZE_EMAIL );
		$userName  = $get( 'userName' );
		$role      = RoleValidator::getTheHighestPossibleIfNotValid( $get( 'wpRole' ) );

		if ( $email && $userName && $role ) {
			$userId = User::insert(
				[
					'first_name' => $firstName,
					'last_name'  => $lastName,
					'user_email' => $email,
					'user_login' => $userName,
					'role'       => $role,
					'user_pass'  => wp_generate_password(),
				]
			);

			return WordPress::handleError( $userId )
			                ->bimap( invoke( 'get_error_messages' ), User::notifyNew() )
			                ->bimap( Lst::join( ', ' ), User::get() );
		} else {
			return Either::left( __( 'The user could not be created', 'sitepress' ) );
		}
	}

}
