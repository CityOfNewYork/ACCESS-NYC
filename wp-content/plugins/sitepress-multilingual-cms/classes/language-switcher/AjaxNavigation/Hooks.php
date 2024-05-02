<?php

namespace WPML\LanguageSwitcher\AjaxNavigation;

class Hooks implements \IWPML_Frontend_Action, \IWPML_DIC_Action {

	public function add_hooks() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueScripts' ] );
	}

	public function enqueueScripts() {
		if ( $this->isEnabled() ) {
			wp_enqueue_script(
				'wpml-ajax-navigation',
				ICL_PLUGIN_URL . '/dist/js/ajaxNavigation/app.js',
				[],
				ICL_SITEPRESS_VERSION
			);
		}
	}

	/**
	 * @return bool
	 */
	private function isEnabled() {
		/**
		 * This filter allows to enable/disable the feature to automatically
		 * refresh the language switchers on AJAX navigation.
		 *
		 * @since 4.4.0
		 *
		 * @param bool $is_enabled Is the feature enabled (default: false).
		 */
		return apply_filters( 'wpml_ls_enable_ajax_navigation', false );
	}
}
