<?php

use WPML\FP\Obj;

class WPML_Theme_Plugin_Localization_Options_Ajax implements IWPML_AJAX_Action, IWPML_DIC_Action {

	const NONCE_LOCALIZATION_OPTIONS = 'wpml-localization-options-nonce';

	/** @var WPML_Save_Themes_Plugins_Localization_Options */
	private $save_localization_options;

	/**
	 * WPML_Themes_Plugins_Localization_Options_Ajax constructor.
	 *
	 * @param WPML_Save_Themes_Plugins_Localization_Options $save_localization_options
	 */
	public function __construct( WPML_Save_Themes_Plugins_Localization_Options $save_localization_options ) {
		$this->save_localization_options = $save_localization_options;
	}

	public function add_hooks() {
		add_action( 'wp_ajax_wpml_update_localization_options', array( $this, 'update_localization_options' ) );
	}

	public function update_localization_options() {
		if ( ! $this->is_valid_request() ) {
			wp_send_json_error();
		} else {
			$this->save_localization_options->save_settings( $_POST );
			wp_send_json_success();
		}
	}

	/** @return bool */
	private function is_valid_request() {
		return wp_verify_nonce( Obj::propOr( '', 'nonce', $_POST ), self::NONCE_LOCALIZATION_OPTIONS );
	}
}
