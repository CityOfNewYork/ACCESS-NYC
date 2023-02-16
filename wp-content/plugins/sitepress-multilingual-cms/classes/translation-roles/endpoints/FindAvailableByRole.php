<?php

namespace WPML\TranslationRoles;

use WPML\Collect\Support\Collection;
use WPML\Ajax\IHandler;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use function WPML\FP\invoke;

class FindAvailableByRole implements IHandler {

	const USER_SEARCH_LIMIT = 10;

	/**
	 * @inheritDoc
	 */
	public function run( Collection $data ) {
		$search  = filter_var( $data->get( 'search' ), FILTER_SANITIZE_STRING );
		$records = [
			'translator' => \WPML_Translator_Records::class,
			'manager'    => \WPML_Translation_Manager_Records::class,
		];

		return Either::of( $data->get( 'role' ) )
		             ->filter( Lst::includes( Fns::__, Obj::keys( $records ) ) )
		             ->map( Obj::prop( Fns::__, $records ) )
		             ->map( Fns::make() )
		             ->map( invoke( 'search_for_users_without_capability' )->with( $search, self::USER_SEARCH_LIMIT ) );
	}
}
