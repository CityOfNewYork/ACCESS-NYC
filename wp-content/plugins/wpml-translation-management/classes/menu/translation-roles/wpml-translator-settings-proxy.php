<?php

class WPML_Translator_Settings_Proxy implements WPML_Translator_Settings_Interface {
	/** @var callable */
	private $create_callback;

	/**
	 * @param callable $create_callback
	 */
	public function __construct( $create_callback ) {
		$this->create_callback = $create_callback;
	}

	public function render() {
		$translator_settings = call_user_func($this->create_callback);
		if (! $translator_settings instanceof WPML_Translator_Settings_Interface) {
			throw new RuntimeException( 'Factory method created an invalid object.' );
		}

		return $translator_settings->render();
	}
}