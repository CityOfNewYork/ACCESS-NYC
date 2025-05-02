<?php
namespace WPML\StringTranslation\UserInterface\RestApi;

use WPML\Rest\Adaptor;
use WPML\StringTranslation\Application\Translation\Query\Dto\TranslationStatusDto;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchCriteria;
use WPML\StringTranslation\Application\StringCore\Query\Dto\StringWithTranslationStatusDto;
use WPML\StringTranslation\Application\StringCore\Query\FindBySearchCriteriaQueryInterface;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;

class StringListApiController extends AbstractStringItemApiController {

	/** @var FindBySearchCriteriaQueryInterface */
	private $findBySearchCriteriaQuery;

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	public function __construct(
		Adaptor $adaptor,
		FindBySearchCriteriaQueryInterface $findBySearchCriteriaQuery,
		SettingsRepositoryInterface $settingsRepository
	) {
		parent::__construct( $adaptor );
		$this->findBySearchCriteriaQuery = $findBySearchCriteriaQuery;
		$this->settingsRepository        = $settingsRepository;
	}

	/**
	 * @return array
	 */
	function get_routes() {
		return [
			[
				'route' => 'strings',
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
			$request->get_param( 'translationStatuses' ) ?? [],
			$request->get_param( 'limit' ),
			$request->get_param( 'offset' ),
			$request->get_param( 'sorting' )
		);

		$items = $this->findBySearchCriteriaQuery->execute( $criteria );

		return array_map(
			function( StringWithTranslationStatusDto $string ) {
				$translations = array_map(
					function ( TranslationStatusDto $translation ) {
						return $translation->toArray();
					},
					$string->getTranslationStatuses()
				);
				$languageDetails = $this->settingsRepository->getLanguageDetails( $string->getLanguage() );

				return [
					'id'                  => $string->getId(),
					'language'            => $string->getLanguage(),
					'languageFullName'    => $languageDetails['languageFullName'],
					'languageFlagUrl'     => $languageDetails['languageFlagUrl'],
					'domain'              => $string->getDomain(),
					'context'             => $string->getContext(),
					'name'                => $string->getName(),
					'title'               => $string->getValue(),
					'status'              => $string->getStatus(),
					'translationPriority' => $string->getTranslationPriority(),
					'sources'             => $string->getSources(),
					'kind'                => $string->getKind(),
					'type'                => $string->getType(),
					'translations'        => $translations,
					'wordCount'           => apply_filters( 'wpml_word_count_calculate_string', $string->getWordCount(), $string->getId() ),
				];
			},
			$items
		);
	}
}
