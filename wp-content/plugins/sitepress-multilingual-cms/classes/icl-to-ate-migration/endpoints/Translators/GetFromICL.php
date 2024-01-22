<?php

namespace WPML\ICLToATEMigration\Endpoints\Translators;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML_TM_ATE_API;

class GetFromICL implements IHandler {

	/**
	 * @var WPML_TM_ATE_API
	 */
	private $apiClient;

	public function __construct( WPML_TM_ATE_API $apiClient ) {
		$this->apiClient = $apiClient;
	}

	public function run( Collection $data ) {
		global $sitepress_settings;
		$translationServiceData = current( $sitepress_settings['icl_translation_projects'] );

		$result = $this->apiClient->import_icl_translators( $translationServiceData['ts_id'], $translationServiceData['ts_access_key'] );

		if ( Fns::isLeft( $result ) ) {
			return Either::left( __( 'Error happened! Please try again.', 'sitepress-multilingual-cms' ) );
		}

		return GetFromICLResponseMapper::map( $result->get()->records );
	}
}
