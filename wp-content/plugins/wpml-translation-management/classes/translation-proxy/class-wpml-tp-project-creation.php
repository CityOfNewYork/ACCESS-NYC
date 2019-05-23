<?php

class WPML_TP_Project_Creation extends WPML_TP_Project_User {

	/**
	 * @var WPML_Translation_Proxy_Networking
	 */
	private $networking;

	/** @var  array $params */
	private $params;

	/** @var  SitePress $sitepress */
	private $sitepress;

	/**
	 * WPML_TP_Project_Creation constructor.
	 *
	 * @param TranslationProxy_Project          $project
	 * @param SitePress                         $sitepress
	 * @param WPML_Translation_Proxy_Networking $networking
	 */
	public function __construct(
		&$project,
		&$sitepress,
		&$networking
	) {
		parent::__construct( $project );
		$this->networking = &$networking;
		$this->sitepress  = &$sitepress;
	}

	/**
	 * Creates a translation project in TP.
	 *
	 * @param array $project
	 * @param array $client
	 *
	 * @return object
	 *
	 * @throws RuntimeException in case the project could not be created
	 */
	public function run( array $project, array $client ) {
		$service = $this->project->service();
		$params  = array(
			'service'       => array( 'id' => $service->id ),
			'project'       => $project,
			'custom_fields' => $service->custom_fields_data,
			'client'        => $client,
		);

		try {
			$response = $this->networking->send_request( OTG_TRANSLATION_PROXY_URL . '/projects.json',
				$params, 'POST' );
			if ( empty( $response->project->id ) ) {
				throw new RuntimeException( 'Response: `' . serialize( $response ) . '` did not contain a valid project!' );
			}
		} catch ( Exception $e ) {
			$invalidation = new WPML_TP_Service_Invalidation( $this->sitepress );
			$invalidation->run();

			throw new RuntimeException( 'Could not create project with params: `' . serialize( $this->params ) . '`',
				0, $e );
		}

		return $response->project;
	}
}