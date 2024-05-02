<?php

use WPML\FP\Obj;
use WPML\FP\Fns;
use WPML\FP\Relation;
use WPML\TM\ATE\AutoTranslate\Endpoint\SyncLock;
use WPML\TM\ATE\Jobs;
use WPML\TM\Menu\TranslationQueue\PostTypeFilters;
use WPML\UIPage;
use WPML\TM\ATE\Review\ApproveTranslations;
use WPML\TM\ATE\Review\Cancel;
use WPML\TM\Jobs\Endpoint\Resign;
use WPML\TM\API\Basket;
use WPML\TM\API\Translators;
use WPML\Element\API\Languages;
use function WPML\FP\pipe;
use function WPML\FP\System\sanitizeString;

class WPML_TM_Jobs_List_Script_Data {

	const TM_JOBS_PAGE = 'tm-jobs';
	const TRANSLATION_QUEUE_PAGE = 'translation-queue';

	private $exportAllToXLIFFLimit;

	/** @var WPML_TM_Rest_Jobs_Language_Names */
	private $language_names;

	/** @var WPML_TM_Jobs_List_Translated_By_Filters */
	private $translated_by_filter;

	/** @var WPML_TM_Jobs_List_Translators */
	private $translators;

	/** @var WPML_TM_Jobs_List_Services */
	private $services;

	/**
	 * @param WPML_TM_Rest_Jobs_Language_Names|null $language_names
	 * @param WPML_TM_Jobs_List_Translated_By_Filters|null $translated_by_filters
	 * @param WPML_TM_Jobs_List_Translators|null $translators
	 * @param WPML_TM_Jobs_List_Services|null $services
	 */
	public function __construct(
		WPML_TM_Rest_Jobs_Language_Names $language_names = null,
		WPML_TM_Jobs_List_Translated_By_Filters $translated_by_filters = null,
		WPML_TM_Jobs_List_Translators $translators = null,
		WPML_TM_Jobs_List_Services $services = null
	) {
		if ( ! $language_names ) {
			global $sitepress;

			$language_names = new WPML_TM_Rest_Jobs_Language_Names( $sitepress );
		}
		$this->language_names = $language_names;

		if ( ! $translators ) {
			global $wpdb;
			$translators = new WPML_TM_Jobs_List_Translators(
				new WPML_Translator_Records(
					$wpdb,
					new WPML_WP_User_Query_Factory(),
					wp_roles()
				)
			);
		}

		if ( ! $services ) {
			$services = new WPML_TM_Jobs_List_Services( new  WPML_TM_Rest_Jobs_Translation_Service() );
		}

		if ( ! $translated_by_filters ) {
			$translated_by_filters = new WPML_TM_Jobs_List_Translated_By_Filters( $services, $translators );
		}

		if ( ! defined( 'WPML_EXPORT_ALL_TO_XLIFF_LIMIT' ) ) {
			define( 'WPML_EXPORT_ALL_TO_XLIFF_LIMIT', 1700 );
		}

		$this->exportAllToXLIFFLimit = WPML_EXPORT_ALL_TO_XLIFF_LIMIT;

		$this->translated_by_filter = $translated_by_filters;
		$this->translators          = $translators;
		$this->services             = $services;
	}

	/**
	 * @return array
	 */
	public function get() {
		$translation_service = TranslationProxy::get_current_service();
		if ( $translation_service ) {
			$translation_service = [
				'id'   => $translation_service->id,
				'name' => $translation_service->name,
			];
		}

		$isATEEnabled = \WPML_TM_ATE_Status::is_enabled_and_activated();

		$data = [
			'isATEEnabled'        => $isATEEnabled,
			'ateJobsToSync'       => $isATEEnabled ? Jobs::getJobsToSync() : [],
			'languages'           => $this->language_names->get_active_languages(),
			'translatedByFilters' => $this->translated_by_filter->get(),
			'localTranslators'    => $this->translators->get(),
			'translationServices' => $this->services->get(),
			'isBasketUsed'        => Basket::shouldUse(),
			'translationService'  => $translation_service,
			'siteKey'             => WP_Installer::instance()->get_site_key( 'wpml' ),
			'batchUrl'            => OTG_TRANSLATION_PROXY_URL . '/projects/%d/external',
			'endpoints'           => [
				'syncLock'                   => SyncLock::class,
				'approveTranslationsReviews' => ApproveTranslations::class,
				'cancelTranslationReviews'   => Cancel::class,
				'resign'                     => Resign::class,
			],
			'types'               => $this->getTypesForFilter(),
			'queryFilters'        => $this->getFiltersFromUrl(),
			'page'                => UIPage::isTMJobs( $_GET ) ? self::TM_JOBS_PAGE : self::TRANSLATION_QUEUE_PAGE,
			'reviewMode'          => \WPML\Setup\Option::getReviewMode(),
		];

		if ( UIPage::isTranslationQueue( $_GET ) ) {
			global $sitepress;
			$tmXliffVersion = $sitepress->get_setting( 'tm_xliff_version' );

			$data['xliffExport'] = [
				'nonce'               => wp_create_nonce( 'xliff-export' ),
				'translationQueueURL' => UIPage::getTranslationQueue(),
				'xliffDefaultVersion' => $tmXliffVersion > 0 ? $tmXliffVersion : 12,
				'xliffExportAllLimit' => $this->exportAllToXLIFFLimit,
			];

			$data['hasTranslationServiceJobs'] = $this->hasTranslationServiceJobs();

			$data['languagePairs'] = $this->buildLanguagePairs( Translators::getCurrent()->language_pairs );
		} else {
			$data['languagePairs'] = $this->getAllPossibleLanguagePairs();
		}

		return $data;
	}


	private function getAllPossibleLanguagePairs() {
		$languages = Languages::getActive();

		$createPair = function ( $currentLanguage ) use ( $languages ) {
			$targets = Fns::reject( Relation::propEq( 'code', Obj::prop( 'code', $currentLanguage ) ), $languages );

			return [ 'source' => $currentLanguage, 'targets' => $targets ];
		};

		$buildEntity = Obj::evolve( [
			'source'  => $this->extractDesiredPropertiesFromLanguage(),
			'targets' => Fns::map( $this->extractDesiredPropertiesFromLanguage() ),
		] );

		return \wpml_collect( $languages )
			->map( $createPair )
			->map( $buildEntity )
			->values()
			->all();
	}

	private function buildLanguagePairs( $pairs ) {
		$getLanguageDetails = Fns::memorize(
			pipe(
				Languages::getLanguageDetails(),
				$this->extractDesiredPropertiesFromLanguage()
			)
		);

		$buildPair = function ( $targetCodes, $sourceCode ) use ( $getLanguageDetails ) {
			$source  = $getLanguageDetails( $sourceCode );
			$targets = Fns::map( $getLanguageDetails, $targetCodes );

			return [ 'source' => $source, 'targets' => $targets ];
		};

		return \wpml_collect( $pairs )->map( $buildPair )->values()->toArray();
	}

	/**
	 * @return Closure
	 */
	private function extractDesiredPropertiesFromLanguage() {
		return function ( $language ) {
			return [
				'code' => Obj::prop( 'code', $language ),
				'name' => Obj::prop( 'display_name', $language ),
			];
		};
	}

	private function getTypesForFilter() {
		$postTypeFilters = new PostTypeFilters( wpml_tm_get_jobs_repository( true, false ) );

		return \wpml_collect( $postTypeFilters->get( [ 'include_unassigned' => true ] ) )
			->map( function ( $label, $name ) {
				return [ 'name' => $name, 'label' => $label ];
			} )
			->values();
	}

	private function getFiltersFromUrl() {
		$filters = [];
		$getProp = sanitizeString();

		if ( Obj::propOr( false, 'element_type', $_GET ) ) {
			$filters['element_type'] = $getProp( Obj::prop( 'element_type', $_GET ) );
		}

		if ( Obj::propOr( false, 'targetLanguages', $_GET ) ) {
			$filters['targetLanguages'] = explode(
				',',
				urldecode(
					$getProp(
						Obj::prop( 'targetLanguages', $_GET )
					)
				)
			);
		}

		if ( Obj::propOr( false, 'status', $_GET ) ) {
			$filters['status'] = [ $getProp( Obj::prop( 'status', $_GET ) ) ];
		}

		if ( Obj::propOr( false, 'only_automatic', $_GET ) ) {
			$filters['translated_by'] = 'automatic';
		}

		return $filters;
	}

	/**
	 * @return bool
	 */
	private function hasTranslationServiceJobs() {
		$searchParams = new WPML_TM_Jobs_Search_Params();
		$searchParams->set_scope( WPML_TM_Jobs_Search_Params::SCOPE_REMOTE );

		$repository = wpml_tm_get_jobs_repository();

		return $repository->get_count( $searchParams ) > 0;
	}
}

