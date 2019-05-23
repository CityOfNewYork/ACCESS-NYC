<?php

abstract class WPML_TP_API {
	/** @var WPML_TP_API_Client */
	protected $client;

	/** @var WPML_TP_Project */
	protected $project;

	/** @var WPML_TP_API_Log_Interface */
	protected $logger;

	public function __construct( WPML_TP_API_Client $client, WPML_TP_Project $project, WPML_TP_API_Log_Interface $logger = null ) {
		$this->client = $client;
		$this->project = $project;
		$this->logger = $logger;
	}

	protected function log( $action, array $params = array() ) {
		if ( null !== $this->logger ) {
			$this->logger->log( $action, $params );
		}
	}
}