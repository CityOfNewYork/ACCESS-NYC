<?php

class WPML_TP_Project_API extends WPML_TP_API {
	
	public function refresh_language_pairs() {
		$this->log( 'Refresh language pairs -> Request sent' );

		$request = new WPML_TP_API_Request( '/projects' );
		$request->set_method( 'PUT' );
		$request->set_params( array(
			'project' => array( 'refresh_language_pairs' => 1 ),
			'refresh_language_pairs' => 1,
			'project_id'             => $this->project->get_id(),
			'accesskey'              => $this->project->get_access_key(),
		) );

		$this->client->send_request( $request );
	}


}