<?php
namespace WPML\StringTranslation\UserInterface\RestApi;

use WPML\Rest\Adaptor;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\FetchFiltersCriteria;
use WPML\StringTranslation\Application\StringCore\Query\FetchFiltersQueryInterface;

class StringFiltersApiController extends AbstractStringItemApiController {

	/** @var FetchFiltersQueryInterface */
	private $findFilterDataQuery;

	public function __construct(
		Adaptor $adaptor,
		FetchFiltersQueryInterface $findFilterDataQuery
	) {
		parent::__construct( $adaptor );
		$this->findFilterDataQuery = $findFilterDataQuery;
	}

	/**
	 * @return array
	 */
	function get_routes() {
		return [
			[
				'route' => 'strings/filters',
				'args'  => [
					'methods'  => 'GET',
					'callback' => [ $this, 'get' ],
					'args'     => $this->getValidParametersForItems(),
				]
			]
		];
	}

	protected function getValidParametersForItems( array $extend = [] ) {
		return $extend;
	}

	/**
	 * @return array
	 * @throws \WPML\Auryn\InjectionException
	 */
	public function get( \WP_REST_Request $request ) {
		$criteria = new FetchFiltersCriteria(
			$request->get_param( 'kind' ),
			$request->get_param( 'type' ),
			$request->get_param( 'source' ),
			$request->get_param( 'domain' ),
			$request->get_param( 'title' ),
			$request->get_param( 'translationPriority' ),
			$request->get_param( 'sourceLanguageCode' ),
			$request->get_param( 'translationStatuses' ) ?? []
		);

		$stringFilterData = $this->findFilterDataQuery->execute( $criteria );

		return [
			'domains'               => $stringFilterData->getDomains(),
			'translationPriorities' => $stringFilterData->getTranslationPriorities(),
		];
	}
}