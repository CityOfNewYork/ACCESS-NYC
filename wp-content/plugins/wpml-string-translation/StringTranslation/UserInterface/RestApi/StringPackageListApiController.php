<?php
namespace WPML\StringTranslation\UserInterface\RestApi;

use WPML\StringTranslation\Application\Translation\Query\Dto\TranslationStatusDto;
use WPML\Rest\Adaptor;
use WPML\StringTranslation\Application\StringPackage\Query\Criteria\StringPackageCriteria;
use WPML\StringTranslation\Application\StringPackage\Query\Dto\StringPackageWithTranslationStatusDto;
use WPML\StringTranslation\Application\StringPackage\Query\FindStringPackagesQueryInterface;
use WPML\StringTranslation\Application\StringPackage\Repository\WidgetPackageRepositoryInterface;

class StringPackageListApiController extends AbstractItemApiController {

	/** @var FindStringPackagesQueryInterface */
	private $findStringPackagesQuery;

	/** @var WidgetPackageRepositoryInterface */
	private $widgetPackageRepository;

	public function __construct(
		Adaptor $adaptor,
		FindStringPackagesQueryInterface $findStringPackagesQuery,
		WidgetPackageRepositoryInterface $widgetPackageRepository
	) {
		parent::__construct( $adaptor );
		$this->findStringPackagesQuery = $findStringPackagesQuery;
		$this->widgetPackageRepository = $widgetPackageRepository;
	}

	/**
	 * @return array
	 */
	function get_routes() {
		return [
			[
				'route' => 'string-packages',
				'args'  => [
					'methods'  => 'GET',
					'callback' => [ $this, 'get' ],
					'args'     => $this->getValidParameters()
				]
			]
		];
	}

	/**
	 * @param StringPackageWithTranslationStatusDto $stringPackage
	 * @return int|mixed|null
	 */
	function getStringPackageCreatedAt( StringPackageWithTranslationStatusDto $stringPackage ) {
		$date = apply_filters(
			'wpml_tm_dashboard_date',
			time(),
			$stringPackage->getId(),
			$stringPackage->getType()
		);
		if ( $date ) {
			return date( 'c', $date );
		}
		return '';
	}

	protected function getValidParametersForItems( array $extend = [] ) {
		return $extend;
	}

	/**
	 * @return array
	 * @throws \WPML\Auryn\InjectionException
	 */
	public function get( \WP_REST_Request $request ) {
		$criteria = new StringPackageCriteria(
			$request->get_param( 'type' ),
			$request->get_param( 'title' ),
			$request->get_param( 'sourceLanguageCode' ),
			$request->get_param( 'targetLanguageCode' ),
			$request->get_param( 'translationStatuses' ),
			$request->get_param( 'limit' ),
			$request->get_param( 'offset' ),
			$request->get_param( 'sorting' )
		);

		$items = $this->findStringPackagesQuery->execute( $criteria );

		if ( ! $items ) {
			return [];
		}

		return array_map(
			function( StringPackageWithTranslationStatusDto $stringPackage ) {
				$translations = array_map(
					function ( TranslationStatusDto $translation ) {
						return $translation->toArray();
					},
					$stringPackage->getTranslationStatuses()
				);

				return [
					'id'             => $stringPackage->getId(),
					'title'          => $this->transformTitle( $stringPackage ),
					'lastEdit'       => $stringPackage->getLastEdit(),
					'type'           => $stringPackage->getType(),
					'translations'   => $translations,
					'wordCount'      => apply_filters( 'wpml_word_count_calculate_package', $stringPackage->getWordCount(), $stringPackage->getId() ),
					'translatorNote' => $stringPackage->getTranslatorNote(),
					'viewLink'       => self::filterViewLink( '', $stringPackage->getId(), $stringPackage->getType() ),
					'editLink'       => self::filterEditLink( '', $stringPackage->getId(), $stringPackage->getType() ),
					'createdAt'      => $this->getStringPackageCreatedAt( $stringPackage ),
					'status'         => $this->getStringPackageStatus( $stringPackage )
				];
			},
			$items
		);
	}

	private function transformTitle( StringPackageWithTranslationStatusDto $stringPackage ): string {
		if ( $this->widgetPackageRepository->isWidgetPackage( $stringPackage ) ) {
			return $this->widgetPackageRepository->getUpdatedTitle( $stringPackage->getTitle() );
		}

		return $stringPackage->getTitle();
	}

	public static function filterViewLink( string $viewLink, int $stringPackageId, string $stringPackageType ): string {
		return self::filterLink( 'wpml_document_view_item_link', 'View', $viewLink, $stringPackageId, $stringPackageType );
	}

	public static function filterEditLink( string $editLink, int $stringPackageId, string $stringPackageType ): string {
		return self::filterLink( 'wpml_document_edit_item_link', 'Edit', $editLink, $stringPackageId, $stringPackageType );
	}

	private function getStringPackageStatus( StringPackageWithTranslationStatusDto $stringPackage ) {
		return apply_filters(
			'wpml_tm_dashboard_status',
			'external',
			$stringPackage->getId(),
			$stringPackage->getType()
		);
	}

	private static function filterLink( string $hook, string $deprecatedLabel, string $link, int $stringPackageId, string $stringPackageType ) {
		$maybeExtractUrlFromLinkTag = function( string $linkOrUrl ): string {
			$htmlLinkPattern = '/<a\s+(?:[^>]*?\s+)?href="([^"]*)"/i';
			preg_match( $htmlLinkPattern, $linkOrUrl, $matches );

			return $matches[1] ?? $linkOrUrl;
		};

		$legacyObject = (object) [
			'ID'              => $stringPackageId,
			'original_doc_id' => $stringPackageId,
		];

		return $maybeExtractUrlFromLinkTag(
			(string) apply_filters( $hook, $link, $deprecatedLabel, $legacyObject, 'package', $stringPackageType )
		);
	}
}
