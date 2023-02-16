<?php

namespace WPML\ST\Shortcode;

use WPML\FP\Fns;
use WPML\FP\Obj;
use function WPML\FP\curryN;
use function WPML\FP\invoke;
use function WPML\FP\partial;

class Hooks implements \IWPML_DIC_Action, \IWPML_Backend_Action, \IWPML_AJAX_Action, \IWPML_REST_Action {
	/** @var \WPML_ST_DB_Mappers_Strings */
	private $stringMapper;

	public function __construct( \WPML_ST_DB_Mappers_Strings $stringMapper ) {
		$this->stringMapper = $stringMapper;
	}


	public function add_hooks() {
		/**
		 * @see \WPML\ST\Shortcode\TranslationHandler::appendId
		 */
		add_filter( 'wpml_tm_xliff_unit_field_data', $this->appendId() );

		$this->defineRetrievingATEJobHooks();
		$this->defineRetrievingProxyJobHooks();
		$this->defineCTEHooks();
	}

	private function defineRetrievingATEJobHooks() {
		$lens = LensFactory::createLensForJobData();

		/**
		 * @see \WPML\ST\Shortcode\TranslationHandler::restoreOriginalShortcodes
		 */
		add_filter( 'wpml_tm_ate_job_data_from_xliff', TranslationHandler::registerStringTranslation( $lens ), 9, 2 );

		/**
		 * @see \WPML\ST\Shortcode\TranslationHandler::restoreOriginalShortcodes
		 */
		add_filter( 'wpml_tm_ate_job_data_from_xliff', $this->restoreOriginalShortcodes( $lens ), 10, 1 );
	}

	private function defineRetrievingProxyJobHooks() {
		$lens = LensFactory::createLensForProxyTranslations();

		$registerStringTranslation = $this->registerStringTranslation( $lens, invoke( 'get_target_language' ) );

		/**
		 * @see \WPML\ST\Shortcode\TranslationHandler::restoreOriginalShortcodes
		 */
		add_filter( 'wpml_tm_proxy_translations', $registerStringTranslation, 9, 1 );

		/**
		 * @see \WPML\ST\Shortcode\TranslationHandler::restoreOriginalShortcodes
		 */
		add_filter( 'wpml_tm_proxy_translations', $this->restoreOriginalShortcodes( $lens ), 10, 1 );
	}

	private function defineCTEHooks() {
		$appendStringIdToFieldsData = Obj::over(
			LensFactory::createLensForAssignIdInCTE(),
			Fns::map( $this->appendId() )
		);

		add_filter( 'wpml_tm_adjust_translation_fields', $appendStringIdToFieldsData, 10, 1 );

		$lens = LensFactory::createLensForJobData();

		$registerStringTranslation = $this->registerStringTranslation( $lens, Obj::prop( 'target_lang' ) );

		/**
		 * @see \WPML\ST\Shortcode\TranslationHandler::restoreOriginalShortcodes
		 */
		add_filter( 'wpml_translation_editor_save_job_data', $registerStringTranslation, 9, 1 );

		/**
		 * @see \WPML\ST\Shortcode\TranslationHandler::restoreOriginalShortcodes
		 */
		add_filter( 'wpml_translation_editor_save_job_data', $this->restoreOriginalShortcodes( $lens ), 10, 1 );
	}

	/**
	 * @return callable
	 */
	private function appendId() {
		return TranslationHandler::appendId( [ $this->stringMapper, 'getByDomainAndValue' ] );
	}

	private function restoreOriginalShortcodes( callable $lens ) {
		return TranslationHandler::restoreOriginalShortcodes( [ $this->stringMapper, 'getById' ], $lens );
	}

	private function registerStringTranslation( callable $lens, callable $getTargetLang ) {
		return TranslationHandler::registerStringTranslation( $lens, Fns::__, $getTargetLang );
	}
}
