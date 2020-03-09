<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

class BasketElement {

	/** @var int */
	private $element_id;

	/** @var string */
	private $source_lang;

	/** @var array */
	private $target_langs;

	/**
	 * @param int    $element_id
	 * @param string $source_lang
	 * @param array  $target_languages
	 */
	public function __construct( $element_id, $source_lang, array $target_languages ) {
		$this->element_id   = (int) $element_id;
		$this->source_lang  = $source_lang;
		$this->target_langs = $target_languages;
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
		return 'post';
	}

	/**
	 * @return string
	 */
	public function get_source_lang() {
		return $this->source_lang;
	}

	/**
	 * @return array
	 */
	public function get_target_langs() {
		return $this->target_langs;
	}
}
