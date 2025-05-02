<?php

namespace WPML\ST\BatchAction;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use function WPML\Container\make;
use WPML\FP\Either;
use WPML\ST\StringsRepository;

class CountStringsInDomainWithDifferentPriority implements IHandler {

	/** @var StringsRepository $stringsRepository */
	private $stringsRepository;

	public function __construct(
		StringsRepository $stringsRepository
	) {
		$this->stringsRepository   = $stringsRepository;
	}

	public function run( Collection $data ) {
		$domain   = $data->get( 'domain', false );
		$priority = $data->get( 'priority', false );

		if ( $domain === false || $priority === false ) {
			return Either::left( __( 'Error: please try again', 'wpml-string-translation' ) );
		}

		return Either::of( [
			'totalItemsCount' => $this->stringsRepository->getCountInDomainsByNotPriorities( [ $domain ], [ $priority ] ),
		] );
	}
}
