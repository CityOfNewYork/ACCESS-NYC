<?php

class WPML_TM_Jobs_List_Script_Data {
	/** @var WPML_TM_Rest_Jobs_Language_Names */
	private $language_names;

	/** @var WPML_TM_Jobs_List_Translated_By_Filters */
	private $translated_by_filter;

	/** @var WPML_TM_Jobs_List_Translators */
	private $translators;

	/** @var WPML_TM_Jobs_List_Services */
	private $services;

	/**
	 * @param WPML_TM_Rest_Jobs_Language_Names|null        $language_names
	 * @param WPML_TM_Jobs_List_Translated_By_Filters|null $translated_by_filters
	 * @param WPML_TM_Jobs_List_Translators|null           $translators
	 * @param WPML_TM_Jobs_List_Services|null              $services
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
					new WPML_WP_User_Query_Factory()
				)
			);
		}

		if ( ! $services ) {
			$services = new WPML_TM_Jobs_List_Services( WPML_TM_Rest_Jobs_Translation_Service::create() );
		}

		if ( ! $translated_by_filters ) {
			$translated_by_filters = new WPML_TM_Jobs_List_Translated_By_Filters( $services, $translators );
		}

		$this->translated_by_filter = $translated_by_filters;
		$this->translators          = $translators;
		$this->services             = $services;
	}


	public function get() {
		$translation_service = TranslationProxy::get_current_service();
		if ( $translation_service ) {
			$translation_service = array(
				'id'   => $translation_service->id,
				'name' => $translation_service->name,
			);
		}


		return array(
			'strings'             => array(
				'bulkActions'            => __( 'Bulk actions', 'wpml-translation-management' ),
				'cancelJobs'             => __( 'Cancel jobs', 'wpml-translation-management' ),
				'getTranslations'        => __( 'Get translations', 'wpml-translation-management' ),
				'apply'                  => __( 'Apply', 'wpml-translation-management' ),
				'firstPage'              => __( 'First page', 'wpml-translation-management' ),
				'previousPage'           => __( 'Previous page', 'wpml-translation-management' ),
				'nextPage'               => __( 'Next page', 'wpml-translation-management' ),
				'lastPage'               => __( 'Last page', 'wpml-translation-management' ),
				'listNavigation'         => __( 'Navigation', 'wpml-translation-management' ),
				'totalItemsText'         => __( '0 items', 'wpml-translation-management' ),
				'of'                     => __( 'of', 'wpml-translation-management' ),
				'selectTranslator'       => __( 'Select translator', 'wpml-translation-management' ),
				'assignTranslator'       => __( 'Assign', 'wpml-translation-management' ),
				'externalActions'        => __( 'External Actions', 'wpml-translation-management' ),
				'actions'                => __( 'Actions', 'wpml-translation-management' ),
				'externalActionsTooltip' => __(
					'Actions that will happen on the translation service',
					'wpml-translaton-management'
				),
				'selectAll'              => __( 'Select all', 'wpml-translation-management' ),
				'filters'                => array(
					'title'        => __( 'Title', 'wpml-translation-management' ),
					'anyLanguage'  => __( 'Any language', 'wpml-translation-management' ),
					'languageFrom' => __( 'from', 'wpml-translation-management' ),
					'languageTo'   => __( 'to', 'wpml-translation-management' ),
					'filter'       => __( 'Filter', 'wpml-translation-management' ),
					'asc'          => __( 'Ascending', 'wpml-translation-management' ),
					'desc'         => __( 'Descending', 'wpml-translation-management' ),
					'sort'         => __( 'Sort', 'wpml-translation-management' ),
					'direction'    => __( 'Direction', 'wpml-translation-management' ),
					'firstSort'    => __( 'sort by', 'wpml-translation-management' ),
					'secondSort'   => __( 'then sort by', 'wpml-translation-management' ),
					'translatedBy' => __( 'translated by', 'wpml-translation-management' ),
					'with'         => __( 'with', 'wpml-translation-management' ),
					'sentBetween'  => __( 'sent between', 'wpml-translation-management' ),
					'deadline'     => __( 'with deadline between', 'wpml-translation-management' ),
					'reset'        => __( 'Reset filters', 'wpml-translation-management' ),
					'selectStatus' => __( 'Select status', 'wpml-translation-management' ),
				),
				'progressMessages'       => array(
					'loadingJobs'          => __( 'Loading jobs...', 'wpml-translation-management' ),
					'applyingTranslations' => __( 'Downloading translations...', 'wpml-translation-management' ),
					'syncBatch'            => __( 'Synchronizing batch...', 'wpml-translation-management' ),
					'cancelJobs'           => __( 'Canceling jobs...', 'wpml-translation-management' ),
					'assignTranslator'     => __( 'Assigning translator...', 'wpml-translation-management' ),
					'downloadingXliff'     => __( 'Downloading XLIFF file...', 'wpml-translation-management' ),
				),
				'confirmations'          => array(
					'applyingTranslations' => __( 'Translations downloaded', 'wpml-translation-management' ),
					'syncBatch'            => __( 'Batch synchronization has been sent',
						'wpml-translation-management' ),
					'cancelJobs'           => __( 'Jobs canceled', 'wpml-translation-management' ),
					'assignTranslator'     => __( 'Translator assigned', 'wpml-translation-management' ),
					'downloadingXliff'     => __( 'XLIFF file downloaded', 'wpml-translation-management' ),
				),
				'jobActions'             => array(
					'checkStatus'         => __( 'Check status', 'wpml-translation-management' ),
					'disabledCheckStatus' => __(
						'Translation is ready, no need to check its status',
						'wpml-translation-management'
					),

					'downloadXLIFF' => array(
						'activeIconText' => __( 'Download the translated XLIFF file', 'wpml-translation-management' ),
						'localJob'       => __( 'This is a local job so it does not have an XLIFF file to download',
							'wpml-translation-management' ),
						'notReady'       => __(
							'You cannot download the translation as it has not been completed',
							'wpml-translation-management'
						),
						'canceled'       => __(
							'You cannot download the XLIFF file because TS_NAME has canceled the job',
							'wpml-translation-management'
						),
					),

					'cancel' => array(
						'activeIconText'  => __( 'Cancel job', 'wpml-translation-management' ),
						'notLocalJob'     => __( 'You can cancel only a local job', 'wpml-translation-management' ),
						'alreadyFinished' => __( 'The job is already finished', 'wpml-translation-management' ),
						'alreadyCanceled' => __( 'The job is already canceled', 'wpml-translation-management' ),
					),

					'getTranslations' => array(
						'activeIconText' => __( 'Get translations', 'wpml-translation-management' ),
						'localJob'       => __(
							'You cannot download the translation for a local job',
							'wpml-translation-management'
						),
						'notReady'       => __(
							'You cannot download the translation as it has not been completed',
							'wpml-translation-management'
						),
						'completed'      => __(
							'You have already downloaded this job',
							'wpml-translation-management'
						),
						'canceled'       => __(
							'You cannot download this job because TS_NAME has canceled it',
							'wpml-translation-management'
						),
					),
				),
				'tpJobId' => __( 'TP ID: %d', 'wpml-translation-management' ),
			),
			'settings'            => array(
				'columns'  => WPML_TM_Rest_Jobs_Columns::get_columns(),
				'sortable' => WPML_TM_Rest_Jobs_Columns::get_sortable(),
				'pageSize' => 10,
			),
			'jobStatuses'         => WPML_TM_Jobs_List_Status_Names::get_statuses(),
			'languages'           => $this->language_names->get_active_languages(),
			'translatedByFilters' => $this->translated_by_filter->get(),
			'localTranslators'    => $this->translators->get(),
			'translationService'  => $translation_service,
			'siteKey'             => WP_Installer::instance()->get_site_key( 'wpml' ),
			'batchUrl'            => OTG_TRANSLATION_PROXY_URL . '/projects/%d/external'
		);
	}

}