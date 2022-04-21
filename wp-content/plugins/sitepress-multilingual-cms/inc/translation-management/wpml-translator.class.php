<?php

class WPML_Translator {
	var $ID;
	var $display_name;
	var $user_login;
	var $language_pairs;

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
