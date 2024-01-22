<?php

class WPML_Translator {
	/** @var int */
	public $ID;

	/** @var string */
	public $display_name;

	/** @var string */
	public $user_login;

	/**
	 * Array where key represents a source language code and values are codes of target languages.
	 *
	 * @var array<string, string[]>
	 */
	public $language_pairs;

	/**
	 * @param string $property
	 *
	 * @return int
	 */
	public function __get( $property ) {
		if ( $property == 'translator_id' ) {
			return $this->ID;
		}
		return null;
	}

	/**
	 * @param string $property
	 * @param int $value
	 *
	 * @return null
	 */
	public function __set( $property, $value ) {
		if ( $property == 'translator_id' ) {
			$this->ID = $value;
		}
		return null;
	}
}
