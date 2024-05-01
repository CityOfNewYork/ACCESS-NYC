<?php

namespace WPML\TM\Jobs\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\TM\API\Jobs;

class Resign implements IHandler {

	public function run( Collection $data ) {


		$result = \wpml_collect( $data->get( 'jobIds' ) )
			->filter( Jobs::get() )
			->map( Fns::tap( [ wpml_load_core_tm(), 'resign_translator' ] ) )
			->values()
			->toArray();

		return Either::of( $result );
	}
}
