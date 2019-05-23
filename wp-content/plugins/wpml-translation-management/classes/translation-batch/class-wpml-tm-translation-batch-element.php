<?php

class WPML_TM_Translation_Batch_Element {
	/** @var int */
	private $element_id;

	/** @var string */
	private $element_type;

	/** @var string */
	private $source_lang;

	/** @var array */
	private $target_langs;

	/** @var $media_to_translations */
	private $media_to_translations;

	/**
	 * @param int    $element_id
	 * @param string $element_type
	 * @param string $source_lang
	 * @param array  $target_languages
	 * @param array  $media_to_translations
	 */
	public function __construct(
		$element_id,
		$element_type,
		$source_lang,
		array $target_languages,
		array $media_to_translations = array()
	) {
		if ( ! $element_id ) {
			throw new InvalidArgumentException( 'Element id has to be defined' );
		}

		if ( empty( $element_type ) ) {
			throw new InvalidArgumentException( 'Element type has to be defined' );
		}

		if ( ! is_string( $source_lang ) || empty( $source_lang ) ) {
			throw new InvalidArgumentException( 'Source lang has to be not empty string' );
		}

		if ( empty( $target_languages ) ) {
			throw new InvalidArgumentException( 'Target languages array cannot be empty' );
		}

		$possible_actions = array(
			TranslationManagement::TRANSLATE_ELEMENT_ACTION,
			TranslationManagement::DUPLICATE_ELEMENT_ACTION
		);
		foreach ( $target_languages as $lang => $action ) {
			if ( ! is_string( $lang ) || ! in_array( $action, $possible_actions, true ) ) {
				throw new InvalidArgumentException( 'Target languages must be an associative array with the language code as a key and the action as a numeric value.' );
			}
		}

		$this->element_id            = $element_id;
		$this->element_type          = $element_type;
		$this->source_lang           = $source_lang;
		$this->target_langs          = $target_languages;
		$this->media_to_translations = $media_to_translations;
	}


	/**
	 * @return int
	 */
	public function get_element_id() {
		return $this->element_id;
	}

	/**
	 * @return string
	 */
	public function get_element_type() {
		return $this->element_type;
	}

	/**
	 * @return string
	 */
	public function get_source_lang() {
		return $this->source_lang;
	}

	/**
	 * @return string[]
	 */
	public function get_target_langs() {
		return $this->target_langs;
	}

	/**
	 * @return mixed
	 */
	public function get_media_to_translations() {
		return $this->media_to_translations;
	}
}