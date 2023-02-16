<?php

use WPML\TM\TranslationProxy\Services\Project\Project;
use WPML\TM\TranslationProxy\Services\Project\SiteDetails;

class WPML_TP_Project_API extends WPML_TP_API {

	const API_VERSION       = 1.1;
	const PROJECTS_ENDPOINT = '/projects.json';

	/**
	 * @throws WPML_TP_API_Exception
	 */
	public function refresh_language_pairs() {
		$this->log( 'Refresh language pairs -> Request sent' );

		$request = new WPML_TP_API_Request( self::PROJECTS_ENDPOINT );
		$request->set_method( 'PUT' );
		$request->set_params(
			[
				'project'                => [ 'refresh_language_pairs' => 1 ],
				'refresh_language_pairs' => 1,
				'project_id'             => $this->project->get_id(),
				'accesskey'              => $this->project->get_access_key(),
			]
		);

		$this->client->send_request( $request );
	}

	/**
	 * @param stdClass    $service
	 * @param SiteDetails $site_details
	 *
	 * @return stdClass
	 * @throws WPML_TP_API_Exception
	 */
	public function create_project( \stdClass $service, SiteDetails $site_details ) {
		$project_data = array_merge(
			$site_details->getBlogInfo(),
			[
				'delivery_method'    => $site_details->getDeliveryMethod(),
				'sitekey'            => WP_Installer_API::get_site_key( 'wpml' ),
				'client_external_id' => WP_Installer_API::get_ts_client_id(),
			]
		);

		$params = [
			'api_version'   => self::API_VERSION,
			'service'       => [ 'id' => $service->id ],
			'project'       => $project_data,
			'custom_fields' => $service->custom_fields_data,
			'client'        => $site_details->getClientData(),
		];

		$request = new WPML_TP_API_Request( self::PROJECTS_ENDPOINT );
		$request->set_method( 'POST' );
		$request->set_params( $params );

		return $this->client->send_request( $request );
	}

	/**
	 * @param Project   $project
	 * @param \stdClass $credentials
	 *
	 * @throws WPML_TP_API_Exception
	 */
	public function update_project_credentials( Project $project, \stdClass $credentials ) {
		$request = new WPML_TP_API_Request( self::PROJECTS_ENDPOINT );
		$request->set_method( 'PUT' );
		$request->set_params(
			[
				'api_version' => self::API_VERSION,
				'accesskey'   => $project->accessKey,
				'project'     => [
					'custom_fields' => (array) $credentials,
				],
			]
		);

		$this->client->send_request( $request );
	}

	/**
	 * @param Project $project
	 *
	 * @return array|mixed|stdClass|string
	 * @throws WPML_TP_API_Exception
	 */
	public function get_extra_fields( Project $project ) {
		$params = [
			'accesskey'   => $project->accessKey,
			'api_version' => self::API_VERSION,
			'project_id'  => $project->id,
		];

		$request = new WPML_TP_API_Request( '/projects/{project_id}/extra_fields.json' );
		$request->set_method( 'GET' );
		$request->set_params( $params );

		return $this->client->send_request( $request );
	}
}
