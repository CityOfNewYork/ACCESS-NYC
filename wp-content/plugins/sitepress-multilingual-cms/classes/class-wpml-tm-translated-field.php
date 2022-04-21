<?php

class WPML_TM_Translated_Field {

	private $original;
	private $translation;
	private $finished_state;

	/**
	 * WPML_TM_Translated_Field constructor.
	 *
	 * @param string $original
	 * @param string $translation
	 * @param bool   $finished_state
	 */

	public function __construct( $original, $translation, $finished_state ) {
		$this->original       = $original;
		$this->translation    = $translation;
		$this->finished_state = $finished_state;
	}

	public function get_translation() {
		return $this->translation;
	}

	public function is_finished( $original ) {
		if ( $original == $this->original ) {
			return $this->finished_state;
		} else {
			return false;
		}
	}
}
