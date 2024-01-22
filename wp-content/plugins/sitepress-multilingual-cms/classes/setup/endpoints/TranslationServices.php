<?php

namespace WPML\Setup\Endpoint;


use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Relation;
use WPML\LIB\WP\Http;
use WPML\TM\Geolocalization;
use WPML\TM\Menu\TranslationServices\ActiveServiceRepository;
use WPML\TM\Menu\TranslationServices\ServiceMapper;
use WPML\TM\Menu\TranslationServices\ServicesRetriever;
use function WPML\Container\make;
use function WPML\FP\partialRight;

class TranslationServices implements IHandler {

	public function run( Collection $data ) {
		$tpApi = make( \WPML_TP_Client_Factory::class )->create()->services();

		$serviceMapperFunction = partialRight(
			[ ServiceMapper::class, 'map' ],
			[ ActiveServiceRepository::class, 'getId' ]
		);

		$services = ServicesRetriever::get(
			$tpApi,
			Geolocalization::getCountryByIp( Http::post() ),
			$serviceMapperFunction
		);

		$preferredServiceSUID = \TranslationProxy::get_tp_default_suid();
		$preferredService = false;
		if ( $preferredServiceSUID ) {
			$services = self::filterByPreferred( $services, $preferredServiceSUID );
			$preferredServiceData = \TranslationProxy_Service::get_service_by_suid( $preferredServiceSUID );
			$preferredService = new \WPML_TP_Service( $preferredServiceData );
			$preferredService->set_custom_fields_data();
			$preferredService = $serviceMapperFunction( $preferredService );
		}


		return Either::of( [
			'services'          => $services,
			'preferredService'	=> $preferredService,
			'logoPlaceholder'   => WPML_TM_URL . '/res/img/lsp-logo-placeholder.png',
		] );
	}

	/**
	 * @param array $services
	 * @param string $preferredServiceSUID
	 * @return array
	 */
	private static function filterByPreferred( $services, $preferredServiceSUID ) {
		$preferredService =  \TranslationProxy_Service::get_service_by_suid( $preferredServiceSUID );
		if ( $preferredService ) {
			foreach ( $services as $key => $serviceGroup ) {
				$services[ $key ] = self::filterServices( $serviceGroup, $preferredService->id );
				if ( empty( $services[ $key ]['services'] ) ) {
					unset( $services[ $key ] );
				}
			}
		}
		return array_values( $services );
	}

	/**
	 * @param array $serviceGroup
	 * @param int $serviceId
	 * @return array
	 */
	public static function filterServices( $serviceGroup, $serviceId ) {
		$serviceGroup['services'] = Fns::filter( Relation::propEq( 'id', $serviceId ), $serviceGroup['services'] );

		return $serviceGroup;
	}
}