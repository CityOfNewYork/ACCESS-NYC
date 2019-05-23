<?php

class WPML_PB_String {

	/** @var  string $value */
	private $value;

	/** @var  string $name */
	private $name;
	/** @var  string $title */
	private $title;
	/** @var  string $editor_type */
	private $editor_type;

	/**
	 * String wrap tag.
	 *
	 * @var  string $wrap_tag
	 */
	private $wrap_tag;

	/**
	 * WPML_PB_String constructor.
	 *
	 * @param string $value       String value.
	 * @param string $name        String name.
	 * @param string $title       String title.
	 * @param string $editor_type Editor type used.
	 * @param string $wrap_tag    String wrap tag.
	 */
	public function __construct( $value, $name, $title, $editor_type, $wrap_tag = '' ) {
		$this->value       = $value;
		$this->name        = $name;
		$this->title       = $title;
		$this->editor_type = $editor_type;
		$this->wrap_tag    = $wrap_tag;
	}

	/**
	 * @return string
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * @param string $value
	 */
	public function set_value( $value ) {
		$this->value = $value;
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * @return string
	 */
	public function get_editor_type() {
		return $this->editor_type;
	}

	/**
	 * Get string wrap tag.
	 *
	 * @return string
	 */
	public function get_wrap_tag() {
		return $this->wrap_tag;
	}
}
