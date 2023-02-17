<?php

namespace WPML\ST\StringsFilter;

class Translator {
	/** @var string */
	private $language;

	/** @var TranslationReceiver */
	private $translationReceiver;

	/** @var Translations */
	private $translations;

	/**
	 * @param string              $language
	 * @param TranslationReceiver $translationReceiver
	 */
	public function __construct(
		$language,
		TranslationReceiver $translationReceiver
	) {
		$this->language            = $language;
		$this->translationReceiver = $translationReceiver;
	}

	/**
	 * @param StringEntity $string
	 *
	 * @return TranslationEntity
	 */
	public function translate( StringEntity $string ) {
		if ( $this->translations === null ) {
			$this->translations = new Translations();
		}

		$translation = $this->translations->get( $string );
		if ( ! $translation ) {
			$translation = $this->translationReceiver->get( $string, $this->language );
			$this->translations->add( $string, $translation );
		}

		return $translation;
	}
}
