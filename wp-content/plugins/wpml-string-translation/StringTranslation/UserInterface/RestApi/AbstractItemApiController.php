<?php

namespace WPML\StringTranslation\UserInterface\RestApi;

use WPML\StringTranslation\Application\StringPackage\Query\Criteria\StringPackageCriteria;
use WPML\StringTranslation\Application\StringPackage\Query\Dto\StringPackageWithTranslationStatusDto;
use WPML\Rest\Adaptor;
use WPML\StringTranslation\Application\StringPackage\Query\FindStringPackagesQueryInterface;
use WPML\API\Sanitize;

abstract class AbstractItemApiController extends AbstractController
{
	abstract protected function getValidParametersForItems( array $extend = [] );

	protected function getValidParameters( $extend = [] ) {
		return array_merge( $extend, [
			'type' => [
				'type'    => 'string',
				'sanitize_callback' => [ 'WPML_REST_Arguments_Sanitation', 'string' ],
			],
			'title' => [
				'type' => 'string',
			],
			'sourceLanguageCode' => [
				'type'              => 'string',
				'sanitize_callback' => [ 'WPML_REST_Arguments_Sanitation', 'string' ],
			],
			'targetLanguageCode' => [
				'type'              => 'string',
				'sanitize_callback' => [ 'WPML_REST_Arguments_Sanitation', 'string' ],
			],
			'translationStatuses' => [
				'default'           => [],
				'sanitize_callback' => [ $this, 'sanitizeTranslationStatuses' ],
				'validate_callback' => [ $this, 'validateTranslationStatuses' ],
			],
			'limit' => [
				'type'    => 'integer',
				'default' => 10,
				'validate_callback' => array( 'WPML_REST_Arguments_Validation', 'integer' ),
			],
			'offset' => [
				'type'    => 'integer',
				'default' => 0,
				'validate_callback' => array( 'WPML_REST_Arguments_Validation', 'integer' ),
			],
			'sorting' => [
				'default' => null,
				'validate_callback' => [ \WPML_REST_Arguments_Validation::class, 'is_array' ]
			],
		]);
	}

	/**
	 * @return array
	 */
	abstract function get_routes();

	/**
	 * @param $translationStatuses
	 * @return bool
	 */
	public function validateTranslationStatuses($translationStatuses)
	{
		return is_array($translationStatuses);
	}

	/**
	 * @param $translationStatuses
	 * @return int[]
	 */
	public function sanitizeTranslationStatuses($translationStatuses)
	{
		return array_map('intval', $translationStatuses);
	}
}
