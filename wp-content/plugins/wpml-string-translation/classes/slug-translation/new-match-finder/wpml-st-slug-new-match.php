<?php

class WPML_ST_Slug_New_Match {
	/** @var string */
	private $value;

	/** @var bool */
	private $preserve_original;

	/**
	 * @param string $value
	 * @param bool   $preserve_original
	 */
	public function __construct( $value, $preserve_original ) {
		$this->value             = $value;
		$this->preserve_original = $preserve_original;
	}

	/**
	 * @return string
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * @return bool
	 */
	public function should_preserve_original() {
		return $this->preserve_original;
	}
}