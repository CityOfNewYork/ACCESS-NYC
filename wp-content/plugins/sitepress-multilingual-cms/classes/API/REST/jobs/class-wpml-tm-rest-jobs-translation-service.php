<?php

class WPML_TM_Rest_Jobs_Translation_Service {
	/**
	 * @param string|int $service_id
	 *
	 * @return string
	 */
	public function get_name( $service_id ) {
		$name = '';
		if ( is_numeric( $service_id ) ) {
			$service = $this->get_translation_service( (int) $service_id );
			if ( $service ) {
				$name = $service->name;
			}
		} else {
			$name = __( 'Local', 'wpml-translation-management' );
		}

		return $name;
	}

	private function get_translation_service( $service_id ) {
		$getService = function ( $service_id ) {
			$current_service = TranslationProxy::get_current_service();
			if ( $current_service && $current_service->id === $service_id ) {
				return $current_service;
			} else {
				return TranslationProxy_Service::get_service( $service_id );
			}
		};

		$cachedGetService = \WPML\LIB\WP\Cache::memorize( 'wpml-tm-services', 3600, $getService );

		return $cachedGetService( $service_id );
	}
}
