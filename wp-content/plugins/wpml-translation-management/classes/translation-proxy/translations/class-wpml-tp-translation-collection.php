<?php

class WPML_TP_Translation_Collection implements IteratorAggregate {
	/** @var WPML_TP_Translation[] */
	private $translations;

	/** @var string */
	private $source_language;

	/** @var string */
	private $target_language;

	/**
	 * @param WPML_TP_Translation[] $translations
	 * @param string                $source_language
	 * @param string                $target_language
	 */
	public function __construct( array $translations, $source_language, $target_language ) {
		$this->translations    = $translations;
		$this->source_language = $source_language;
		$this->target_language = $target_language;
	}

	/**
	 * @return string
	 */
	public function get_source_language() {
		return $this->source_language;
	}

	/**
	 * @return string
	 */
	public function get_target_language() {
		return $this->target_language;
	}

	public function getIterator() {
		return new ArrayIterator( $this->translations );
	}

	/**
	 * @return array
	 */
	public function to_array() {
		$translations = array();
		foreach ( $this->translations as $translation ) {
			$translations[] = $translation->to_array();
		}

		return array(
			'source_language' => $this->source_language,
			'target_language' => $this->target_language,
			'translations'    => $translations,
		);
	}
}