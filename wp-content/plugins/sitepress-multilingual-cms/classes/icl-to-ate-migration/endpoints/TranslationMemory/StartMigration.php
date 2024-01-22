<?php

namespace WPML\ICLToATEMigration\Endpoints\TranslationMemory;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML_TM_ATE_API;



class StartMigration implements IHandler {
	
	/**
	 * @var WPML_TM_ATE_API
	 */
	private $apiClient;
	
	/**
	 * @param WPML_TM_ATE_API  $apiClient
	 */
	public function __construct( WPML_TM_ATE_API $apiClient ) {
		$this->apiClient = $apiClient;
	}
	
	public function run( Collection $data ) {
		// call ATE endpoint to trigger translation memory migration
		
		// This should be the final version I comment out because the api is not working as expected
		/*$responde =  $this->apiClient->start_translation_memory_migration();
		return Either::of( $responde );*/
		
		// I have mocked the return of the APi for now, will be removed for the final version
		return Either::of( [
		'started_at'           => null,
		'finished_at'          => null,
		'status'               => 'in_progress',
		'last_imported_icl_id' => 15,
		'imported_count'       => 4
		] );
	}
}
