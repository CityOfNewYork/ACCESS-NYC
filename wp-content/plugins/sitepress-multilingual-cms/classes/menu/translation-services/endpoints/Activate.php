<?php

namespace WPML\TM\Menu\TranslationServices\Endpoints;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\TM\TranslationProxy\Services\AuthorizationFactory;

class Activate implements IHandler {

	public function run( Collection $data ) {
		$serviceId    = $data->get( 'service_id' );
		$apiTokenData = $data->get( 'api_token' );

		$authorize = function ( $serviceId ) use ( $apiTokenData ) {
			$authorization = ( new AuthorizationFactory )->create();
			try {
				foreach( $apiTokenData as $key => $data ) {
					if ( empty( $data ) ) {
						unset( $apiTokenData[ $key ] );
					}
				}
				$authorization->authorize( (object) $apiTokenData );

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