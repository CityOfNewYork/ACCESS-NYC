<?php

namespace WPML\TM\Upgrade\Commands;

use WPML\Settings\PostType\Automatic;
use WPML\Setup\Option;

/**
 * @since 4.7
 *
 * Starting from WPML 4.7, the translate-everything-is-paused is no longer needed, so here we remove it from WPML(setup) options.
 *
 * If the TM is not allowed, we disable TranslateEverything,
 * If TranslateEverything is paused, we disable it,
 * Otherwise the state of TranslateEverything stays as is.
 */
class SetCorrectTranslateEverythingState implements \IWPML_Upgrade_Command {
	public function run() {
		$optionKey                   = 'WPML(setup)';
		$translateEverythingIsPaused = false;

		$wpmlSetupOptions = get_option( $optionKey );

		if ( ! is_array( $wpmlSetupOptions ) ) {
			return true;
		}

		// remove the translate-everything-is-paused from WPML(setup) options.
		if ( isset( $wpmlSetupOptions['translate-everything-is-paused'] ) ) {
			$translateEverythingIsPaused = boolval( $wpmlSetupOptions['translate-everything-is-paused'] );
			unset( $wpmlSetupOptions['translate-everything-is-paused'] );
		}

		$tmNotAllowed = isset( $wpmlSetupOptions[ Option::TM_ALLOWED ] ) &&
		                ! $wpmlSetupOptions[ Option::TM_ALLOWED ];

		$isAnyPostTypeDisabledForAutoTranslate = Automatic::isAnyPostTypeDisabledForAutoTranslate();

		// If TM is not allowed (blog licence) Or TranslateEverything is paused we disable TranslateEverything
		if ( $tmNotAllowed || $translateEverythingIsPaused || $isAnyPostTypeDisabledForAutoTranslate ) {
			$wpmlSetupOptions[ Option::TRANSLATE_EVERYTHING ] = false;
		}

		if ( $translateEverythingIsPaused || $isAnyPostTypeDisabledForAutoTranslate ) {
			/**
			 * Adding this option to TRUE so that we know that user used translate everything before and then.,
			 * we can display proper message to user who paused TranslateEverything or.,
			 * disabled automatic translation for any post type.
			 *
			 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-2143
			 */
			$wpmlSetupOptions[ Option::HAS_TRANSLATE_EVERYTHING_BEEN_EVER_USED ] = true;
		}


		update_option( $optionKey, $wpmlSetupOptions, true );

		return true;
	}

	public function run_admin() {
		return $this->run();
	}

	public function run_ajax() {
		return null;
	}

	public function run_frontend() {
		return null;
	}

	public function get_results() {
		return true;
	}
}
