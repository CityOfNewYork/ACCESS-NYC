<?php

namespace WPML\ST\StringsFilter;

class Translations {
	/** @var \SplObjectStorage */
	private $data;

	public function __construct() {
		$this->data = new TranslationsObjectStorage();
	}


	/**
	 * @param StringEntity      $string
	 * @param TranslationEntity $translation
	 */
	public function add( StringEntity $string, TranslationEntity $translation ) {
		$this->data->attach( $string, $translation );
	}


	/**
	 * @param StringEntity $string
	 *
	 * @return TranslationEntity|null
	 */
	public function get( StringEntity $string ) {
		return $this->data->contains( $string ) ? $this->data[ $string ] : null;
	}
}
