<?php

namespace WPML\TranslationRoles;

use WPML\Collect\Support\Collection;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use function WPML\Container\make;
use function WPML\FP\invoke;
use function WPML\FP\partial;
use function WPML\FP\partialRight;
use function WPML\FP\pipe;

class SaveTranslator extends SaveUser {

	/**
	 * @inheritDoc
	 */
	public function run( Collection $data ) {

		$pairs = wpml_collect( $data->get( 'pairs' ) )
			->filter( pipe( Obj::prop( 'to' ), Lst::length() ) )
			->mapWithKeys( function ( $pair ) { return [ $pair['from'] => $pair['to'] ]; } )
			->toArray();

		// $setRole :: WP_User -> WP_User
		$setRole = Fns::tap( invoke( 'add_cap' )->with( \WPML\LIB\WP\User::CAP_TRANSLATE ) );

		// $storePairs :: int -> int
		$storePairs = Fns::tap( partialRight( [ make( \WPML_Language_Pair_Records::class ), 'store' ], $pairs ) );

		return self::getUser( $data )
		           ->map( $setRole )
		           ->map( Obj::prop( 'ID' ) )
		           ->map( $storePairs )
		           ->map( function( $user ) {
					   do_action( 'wpml_update_translator' );
					   return $user;
				   } )
		           ->map( Fns::always( true ) );
	}
}
