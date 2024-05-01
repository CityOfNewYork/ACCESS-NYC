<?php

namespace WPML\TM\Menu\TranslationServices\Endpoints;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\TM\TranslationProxy\Services\AuthorizationFactory;

class Activate implements IHandler {

	public function run( Collection $data ) {
		$serviceId    = $data->get( 'service_id' );
		$apiTokenData = $data->get( 'api_token' );

		$authorize = function ( $serviceId ) use ( $apiTokenData ) {
			$authorization = ( new AuthorizationFactory )->create();
			try {
				$authorization->authorize( (object) Obj::pickBy( Logic::isNotEmpty(), $apiTokenData ) );

				return Either::of( $serviceId );
			} catch ( \Exception $e ) {
				$authorization->deauthorize();

				return Either::left( $e->getMessage() );
			}
		};

		return Either::of( $serviceId )
		             ->chain( [ Select::class, 'select' ] )
		             ->chain( $authorize );
	}
}