<?php

namespace WPML\Compatibility\Divi;

class Builder implements \IWPML_Frontend_Action, \IWPML_Backend_Action, \IWPML_AJAX_Action {

	public function add_hooks() {
		add_filter( 'theme_locale', [ $this, 'switch_to_user_language' ] );
	}

	public function switch_to_user_language( $locale ) {
		if ( isset( $_POST['action'] ) && ( 'et_fb_update_builder_assets' === $_POST['action'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification
			$locale = get_user_locale();
			switch_to_locale( $locale );
		}

		return $locale;
	}

}

