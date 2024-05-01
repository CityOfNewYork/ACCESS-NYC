<?php

namespace WPML\TranslationRoles;

use WPML\Collect\Support\Collection;
use WPML\Ajax\IHandler;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\LIB\WP\User;
use function WPML\Container\make;
use function WPML\FP\invoke;
use function WPML\FP\pipe;

abstract class Remove implements IHandler {

	/**
	 * @inheritDoc
	 */
	public function run( Collection $data ) {

		// $removeRole :: WP_user -> WP_user
		$removeRole = Fns::tap( invoke( 'remove_cap' )->with( static::getCap() ) );

		// $removeOnlyI :: WP_user -> WP_user
		$removeOnlyI = Fns::tap( pipe( Obj::prop( 'ID' ), User::deleteMeta( Fns::__, \WPML_TM_Wizard_Options::ONLY_I_USER_META ) ) );

		// $doActions :: WP_user -> WP_user
		$doActions = Fns::tap( function ( $user ) {
			do_action( 'wpml_tm_remove_translation_role', $user, static::getCap() );
		} );

		return Either::fromNullable( $data->get( 'ID' ) )
		             ->map( User::get() )
		             ->filter( invoke( 'exists' ) )
		             ->map( $removeRole )
		             ->map( $removeOnlyI )
		             ->map( $doActions )
		             ->bimap( Fns::always( $this->msgUserNotFound() ), Fns::always( true ) );
	}

	abstract protected static function getCap();

	protected function msgUserNotFound() {
		return __( 'User not found', 'sitepress' );
	}
}
