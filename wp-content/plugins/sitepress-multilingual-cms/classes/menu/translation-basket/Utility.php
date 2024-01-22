<?php

namespace WPML\TM\Menu\TranslationBasket;

class Utility {
	/** @var \SitePress */
	private $sitepress;

	/** @var \WPML_Translator_Records */
	private $translatorRecords;

	/**
	 * @param \SitePress               $sitepress
	 * @param \WPML_Translator_Records $translatorRecords
	 */
	public function __construct( \SitePress $sitepress, \WPML_Translator_Records $translatorRecords ) {
		$this->sitepress         = $sitepress;
		$this->translatorRecords = $translatorRecords;
	}


	/**
	 * @return array
	 */
	public function getTargetLanguages() {
		$basketLanguages = \TranslationProxy_Basket::get_target_languages();

		$targetLanguages = [];

		if ( is_array( $basketLanguages ) ) {

			$notBasketLanguage      = function ( $lang ) use ( $basketLanguages ) {
				return ! in_array( $lang['code'], $basketLanguages, true );
			};
			$isBasketSourceLanguage = function ( $lang ) {
				return \TranslationProxy_Basket::get_source_language() === $lang['code'];
			};
			$addFlag                = function ( $lang ) {
				$lang['flag'] = $this->sitepress->get_flag_img( $lang['code'] );

				return $lang;
			};
			$targetLanguages        = wpml_collect( $this->sitepress->get_active_languages() )
				->reject( $notBasketLanguage )
				->reject( $isBasketSourceLanguage )
				->map( $addFlag )
				->toArray();
		}

		return $targetLanguages;
	}

	/**
	 * @param $targetLanguages
	 *
	 * @return bool
	 */
	public function isTheOnlyAvailableTranslatorForTargetLanguages( $targetLanguages ) {
		if ( \TranslationProxy::is_current_service_active_and_authenticated() ) {
			return false;
		}

		$translators = $this->translatorRecords->get_users_with_languages(
			\TranslationProxy_Basket::get_source_language(),
			array_keys( $targetLanguages ),
			false
		);

		return count( $translators ) === 1 && $translators[0]->ID === get_current_user_id();
	}

	/**
	 * @return bool
	 */
	public function isTheOnlyAvailableTranslator() {
		return $this->isTheOnlyAvailableTranslatorForTargetLanguages( $this->getTargetLanguages() );
	}
}
