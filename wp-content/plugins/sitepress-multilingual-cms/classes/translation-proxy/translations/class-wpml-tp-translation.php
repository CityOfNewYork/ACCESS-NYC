<?php

class WPML_TP_Translation {
	/** @var string */
	private $field;

	/** @var string */
	private $source;

	/** @var string */
	private $target;

	/**
	 * @param string $field
	 * @param string $source
	 * @param string $target
	 */
	public function __construct( $field, $source, $target ) {
		$this->field  = $field;
		$this->source = $source;
		$this->target = $target;
	}

	/**
	 * @return string
	 */
	public function get_field() {
		return $this->field;
	}

	/**
	 * @return string
	 */
	public function get_source() {
		return $this->source;
	}

	/**
	 * @return string
	 */
	public function get_target() {
		return $this->target;
	}

	public function to_array() {
		return get_object_vars( $this );
	}
}
