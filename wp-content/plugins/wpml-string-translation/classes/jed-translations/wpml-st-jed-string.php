<?php

class WPML_ST_JED_String {

	/** @var string $original */
	private $original;

	/** @var array $translations */
	private $translations = array();

	/** @var null|string $context */
	private $context;

	/**
	 * @param string      $original
	 * @param array       $translations
	 * @param null|string $context
	 */
	public function __construct( $original, array $translations, $context = null ) {
		$this->original     = $original;
		$this->translations = $translations;
		$this->context      = $context ? $context : null;
	}

	/** @return string */
	public function get_original() {
		return $this->original;
	}

	/** @return array */
	public function get_translations() {
		return $this->translations;
	}

	/** @return null|string */
	public function get_context() {
		return $this->context;
	}
}
