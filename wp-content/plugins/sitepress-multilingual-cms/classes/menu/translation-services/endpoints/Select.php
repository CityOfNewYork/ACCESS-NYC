<?php

namespace WPML\TM\Menu\TranslationServices\Endpoints;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;

class Select implements IHandler {

	public function run( Collection $data ) {
		$serviceId = $data->get( 'service_id' );

		return self::select( $serviceId );
	}

	public static function select( $serviceId ) {
		$deactivateOldService = function () {
			\TranslationProxy::clear_preferred_translation_service();
			\TranslationProxy::deselect_active_service();
		};

		$activateService = function ( $serviceId ) {
			$result = \TranslationProxy::select_service( $serviceId );

			return \is_wp_error( $result ) ? Either::left( $result->get_error_message() ) : Either::of( $serviceId );
		};


		$currentServiceId = \TranslationProxy::get_current_service_id();
		if ( $currentServiceId ) {
			if ( $currentServiceId === $serviceId ) {
				return Either::of( $serviceId );
			} else {
				$deactivateOldService();
			}
		}

		return $activateService( $serviceId );
	}

}