<?php
namespace WPML\TranslationRoles\Service;

use WPML\LIB\WP\User;
use function WPML\Container\make;

class AdministratorRoleManager {

	const USER_METAKEY_INITIALIZED = '_wpml_initialized_administrator_role';

	public function verifyCurrentUser() {
		$user = User::getCurrent();
		return $this->verifyUser( $user );
	}
	public function verifyUserId( $userId, $skipRoleCheck = false )
	{
		$user = User::get($userId);
		return $this->verifyUser( $user, $skipRoleCheck );
	}

	public function initializeAllAdministrators() {
		$administrators = get_users( [ 'role' => 'administrator' ] );
		foreach ( $administrators as $administrator ) {
			$this->initializeAdministrator( $administrator->ID );
		}
	}

	private function verifyUser( $user, $skipRoleCheck = false ) {
		if (
			! $user ||
			( ! $user->has_cap( User::CAP_ADMINISTRATOR ) && ! $skipRoleCheck ) ||
			User::getMetaSingle( $user->ID, self::USER_METAKEY_INITIALIZED) ||
			$user->has_cap( User::CAP_TRANSLATE )
		) {
			return false;
		}
		$this->initializeAdministrator( $user->ID );
		return true;
	}

	private function initializeAdministrator( $user_id ) {
		$user = User::get( $user_id );
		$user->add_cap( \WPML\LIB\WP\User::CAP_TRANSLATE );
		User::updateMeta( $user->ID, \WPML_TM_Wizard_Options::ONLY_I_USER_META, true );
		User::updateMeta( $user->ID, self::USER_METAKEY_INITIALIZED, true );

		make( \WPML_Language_Pair_Records::class )->store(
			$user->ID,
			\WPML_All_Language_Pairs::get()
		);
	}

}
