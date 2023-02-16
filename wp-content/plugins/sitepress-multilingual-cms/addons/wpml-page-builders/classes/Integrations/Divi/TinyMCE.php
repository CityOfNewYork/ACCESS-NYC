<?php

namespace WPML\Compatibility\Divi;

use WPML\FP\Obj;

class TinyMCE implements \IWPML_Backend_Action {

	public function add_hooks() {
		add_filter( 'tiny_mce_before_init', [ $this, 'filterEditorAutoTags' ] );
	}

	/**
	 * @param array $config
	 *
	 * @return array
	 */
	public function filterEditorAutoTags( $config ) {
		if ( did_action( 'admin_init' ) ) {
			$screen = get_current_screen();

			if ( Obj::prop( 'id', $screen ) === 'wpml_page_wpml-translation-management/menu/translations-queue' ) {
				$config['wpautop']      = false;
				$config['indent']       = true;
				$config['tadv_noautop'] = true;
			}
		}

		return $config;
	}
}
