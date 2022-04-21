<?php

namespace WPML\TM\TranslationProxy\Services\Project;

class Manager {

	/** @var \WPML_TP_Project_API */
	private $projectApi;

	/** @var Storage */
	private $projectStorage;

	/** @var SiteDetails */
	private $siteDetails;

	/**
	 * @param \WPML_TP_Project_API $projectApi
	 * @param Storage              $projectStorage
	 * @param SiteDetails          $siteDetails
	 */
	public function __construct(
		\WPML_TP_Project_API $projectApi,
		Storage $projectStorage,
		SiteDetails $siteDetails
	) {
		$this->projectApi     = $projectApi;
		$this->projectStorage = $projectStorage;
		$this->siteDetails    = $siteDetails;
	}

	/**
	 * @param \stdClass $service
	 *
	 * @return Project
	 * @throws \WPML_TP_API_Exception
	 */
	public function create( \stdClass $service ) {
		$project = $this->projectStorage->getByService( $service ) ?: $this->fromTranslationProxy( $service );

		$project->extraFields = $this->projectApi->get_extra_fields( $project );
		$this->projectStorage->save( $service, $project );

		do_action( 'wpml_tp_project_created', $service, $project, $this->projectStorage->getProjects()->toArray() );

		return $project;
	}

	/**
	 * @param \stdClass $service
	 * @param \stdClass $credentials
	 *
	 * @return Project|null
	 * @throws \WPML_TP_API_Exception
	 */
	public function updateCredentials( \stdClass $service, \stdClass $credentials ) {
		$project = $this->projectStorage->getByService( $service );
		if ( ! $project ) {
			throw new \RuntimeException( 'Project does not exist' );
		}
		$this->projectApi->update_project_credentials( $project, $credentials );

		$project->extraFields = $this->projectApi->get_extra_fields( $project );
		$this->projectStorage->save( $this->createServiceWithNewCredentials( $service, $credentials ), $project );

		return $project;
	}

	/**
	 * @param \stdClass $service
	 *
	 * @return Project
	 * @throws \WPML_TP_API_Exception
	 */
	private function fromTranslationProxy( \stdClass $service ) {
		$response = $this->projectApi->create_project( $service, $this->siteDetails );

		return Project::fromResponse( $response->project );
	}

	/**
	 * @param \stdClass $service
	 * @param \stdClass $credentials
	 *
	 * @return \stdClass
	 */
	private function createServiceWithNewCredentials( \stdClass $service, \stdClass $credentials ) {
		$updatedService                     = clone $service;
		$updatedService->custom_fields_data = $credentials;

		return $updatedService;
	}
}
