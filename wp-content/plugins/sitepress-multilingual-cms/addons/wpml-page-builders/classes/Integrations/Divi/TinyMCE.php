<?php

namespace WPML\Compatibility\Divi;

use WPML\FP\Obj;

class TinyMCE implements \IWPML_Backend_Action {

	public function add_hooks() {
		if ( defined( 'WPML_TM_FOLDER' ) ) {
			add_filter( 'tiny_mce_before_init', [ $this, 'filterEditorAutoTags' ] );
		}
	}

	/**
	 * @param array $config
	 *
	 * @return array
	 */
	public function filterEditorAutoTags( $config ) {
		if ( did_action( 'admin_init' ) ) {
			$screen = get_current_screen();
			$cteUrl = 'wpml_page_' . constant( 'WPML_TM_FOLDER' ) . '/menu/translations-queue';

			if ( Obj::prop( 'id', $screen ) === $cteUrl ) {
				$config['wpautop']      = false;
				$config['indent']       = true;
				$config['tadv_noautop'] = true;
			}
		}

		return $config;
	}
}
