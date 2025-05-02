<?php

namespace WPML\ST\BatchAction;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use function WPML\Container\make;
use WPML\FP\Either;
use WPML\ST\StringsRepository;

class CountStringsInDomain implements IHandler {
	public function run( Collection $data ) {
		$domain = $data->get( 'domain', false );

		if ( $domain === false ) {
			return Either::left( __( 'Error: please try again', 'wpml-string-translation' ) );
		}

		/** @var StringsRepository $stringsRepository */
		$stringsRepository = make( StringsRepository::class );

		return Either::of( [
			'totalItemsCount' => $stringsRepository->getCountInDomains( [ $domain ] ),
		] );
	}
}
