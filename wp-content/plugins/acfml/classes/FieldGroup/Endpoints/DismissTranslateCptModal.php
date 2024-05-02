<?php

namespace ACFML\FieldGroup\Endpoints;

use ACFML\FieldGroup\DetectNonTranslatableLocations;
use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Cast;
use WPML\FP\Either;
use WPML\FP\Fns;

class DismissTranslateCptModal implements IHandler {

	/**
	 * @param \WPML\Collect\Support\Collection<mixed> $data
	 *
	 * @return \WPML\FP\Either
	 */
	public function run( Collection $data ) {
		return Either::fromNullable( $data->get( 'fieldGroupId' ) )
			->map( Cast::toInt() )
			->map( [ DetectNonTranslatableLocations::class, 'dismiss' ] )
			->map( Fns::always( true ) );
	}
}
