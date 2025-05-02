<?php
namespace WPML\StringTranslation\UserInterface\RestApi;

use WPML\Rest\Adaptor;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchCriteria;
use WPML\StringTranslation\Application\StringCore\Query\FindCountBySearchCriteriaQueryInterface;

class StringItemsCountApiController extends AbstractStringItemApiController {

	/** @var FindCountBySearchCriteriaQueryInterface */
	private $findCountBySearchCriteriaQuery;

	public function __construct(
		Adaptor $adaptor,
		FindCountBySearchCriteriaQueryInterface $findCountBySearchCriteriaQuery
	) {
		parent::__construct( $adaptor );
		$this->findCountBySearchCriteriaQuery = $findCountBySearchCriteriaQuery;
	}

	/**
	 * @return array
	 */
	function get_routes() {
		return [
			[
				'route' => 'strings/itemscount',
				'args'  => [
					'methods'  => 'GET',
					'callback' => [ $this, 'get' ],
					'args'     => $this->getValidParametersForItems(),
				],
			]
		];
	}

	/**
	 * @return array
	 * @throws \WPML\Auryn\InjectionException
	 */
	public function get( \WP_REST_Request $request ) {
		$criteria = new SearchCriteria(
			$request->get_param( 'kind' ),
			$request->get_param( 'type' ),
			$request->get_param( 'source' ),
			$request->get_param( 'domain' ),
			$request->get_param( 'title' ),
			$request->get_param( 'translationPriority' ),
			$request->get_param( 'sourceLanguageCode' ),
			$request->get_param( 'targetLanguageCode' ),
			$request->get_param( 'translationStatuses' ) ?? []
		);

		$totalCount = $this->findCountBySearchCriteriaQuery->execute( $criteria );

		return [
			'totalCount' => $totalCount,
		];
	}
}
