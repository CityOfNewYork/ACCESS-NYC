<?php

namespace WPML\ICLToATEMigration\Endpoints\TranslationMemory;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\ICLToATEMigration\Data;
use WPML_TM_ATE_API;

class CheckMigrationStatus implements IHandler {
	/**
	 * @var WPML_TM_ATE_API
	 */
	private $apiClient;

	/**
	 * @param WPML_TM_ATE_API $apiClient
	 */
	public function __construct( WPML_TM_ATE_API $apiClient ) {
		$this->apiClient = $apiClient;
	}

	public function run( Collection $data ) {
		$status = 'done';

		if ( 'done' === $status ) {
			Data::setMemoryMigrated( true );
		}

		return Either::of( [
			'started_at'           => null,
			'finished_at'          => null,
			'status'               => $status,
			'last_imported_icl_id' => 15,
			'imported_count'       => 4
		] );
	}
}