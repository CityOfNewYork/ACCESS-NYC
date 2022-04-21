<?php

namespace WPML\TM\TranslationProxy\Services;

use WPML\TM\TranslationProxy\Services\Project\Manager;

class Authorization {
	/** @var Storage */
	private $storage;

	/** @var Manager */
	private $projectManager;

	/**
	 * @param Storage $storage
	 * @param Manager $projectManager
	 */
	public function __construct( Storage $storage, Manager $projectManager ) {
		$this->storage        = $storage;
		$this->projectManager = $projectManager;
	}

	/**
	 * @param \stdClass $credentials
	 *
	 * @throws \RuntimeException
	 * @throws \WPML_TP_API_Exception
	 */
	public function authorize( \stdClass $credentials ) {
		$service                     = $this->getCurrentService();
		$service->custom_fields_data = $credentials;

		$project = $this->projectManager->create( $service );
		$this->storage->setCurrentService( $service );

		do_action( 'wpml_tm_translation_service_authorized', $service, $project );
	}

	/**
	 * @param \stdClass $credentials
	 *
	 * @throws \WPML_TP_API_Exception
	 */
	public function updateCredentials( \stdClass $credentials ) {
		$service = $this->getCurrentService();

		$this->projectManager->updateCredentials( $service, $credentials );

		$service->custom_fields_data = $credentials;
		$this->storage->setCurrentService( $service );
	}

	/**
	 * @throws \RuntimeException
	 */
	public function deauthorize() {
		$service                     = $this->getCurrentService();
		$service->custom_fields_data = null;

		$this->storage->setCurrentService( $service );

		do_action( 'wpml_tp_service_de_authorized', $service );
	}

	/**
	 * @return \stdClass
	 * @throws \RuntimeException
	 */
	private function getCurrentService() {
		$service = $this->storage->getCurrentService();
		if ( (bool) $service === false ) {
			throw new \RuntimeException( 'Tried to authenticate a service, but no service is active!' );
		}

		return $service;
	}
}
