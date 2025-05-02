<?php

namespace WPML\Compatibility\Flatsome;

use WPML\FP\Obj;

class Hooks implements \IWPML_AJAX_Action {

	const SAVE_OPTIONS_ACTION = 'of_ajax_post_action';

	public function add_hooks() {
		add_filter( 'wpml_skip_admin_options_filters', [ $this, 'skipAdminOptionsFiltersOnSave' ] );
	}

	/**
	 * @param bool $state
	 *
	 * @return bool
	 */
	public function skipAdminOptionsFiltersOnSave( $state ) {
		if ( ! wpml_is_ajax() ) {
			return $state;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		return Obj::prop( 'action', $_REQUEST ) === self::SAVE_OPTIONS_ACTION ? true : $state;
	}
}
