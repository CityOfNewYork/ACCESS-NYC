<?php

class WPML_TM_Rest_Jobs_Translation_Service {
	/** @var WPML_WP_Cache */
	private $cache;

	/**
	 * @param WPML_WP_Cache $cache
	 */
	public function __construct( WPML_WP_Cache $cache ) {
		$this->cache = $cache;
	}


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
		$key     = 'service_' . $service_id;
		$found   = false;
		$service = $this->cache->get( $key, $found );

		if ( ! $found ) {
			$current_service = TranslationProxy::get_current_service();
			if ( $current_service && $current_service->id === $service_id ) {
				$service = $current_service;
			} else {
				$service = TranslationProxy_Service::get_service( $service_id );
			}

			$this->cache->set( $key, $service );
		}

		return $service;
	}

	/**
	 * @return WPML_TM_Rest_Jobs_Translation_Service
	 */
	public static function create() {
		$cache = new WPML_WP_Cache( 'wpml-tm-services' );

		return new WPML_TM_Rest_Jobs_Translation_Service( $cache );
	}
}
