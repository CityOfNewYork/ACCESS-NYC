<?php

namespace WPML\Compatibility\Divi;

class Builder implements \IWPML_Frontend_Action, \IWPML_Backend_Action, \IWPML_AJAX_Action {

	public function add_hooks() {
		if ( self::isLoadingAssetsForDiviBuilder() ) {
			add_action( 'init', [ $this, 'switch_to_user_language' ] );
		}
	}

	public function switch_to_user_language() {
		switch_to_locale( get_user_locale() );
	}

	private static function isLoadingAssetsForDiviBuilder(): bool {
		return isset( $_POST['action'] )
			&& 'et_fb_update_builder_assets' === $_POST['action']; // phpcs:ignore WordPress.CSRF.NonceVerification
	}
}
