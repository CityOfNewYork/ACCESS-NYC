<?php

namespace WPML\Compatibility\Divi;

class ConvertThemeOptions implements \IWPML_Frontend_Action {

	public function add_hooks() {
		add_filter( 'et_get_option_et_divi_divi_logo', [ $this, 'filterLogoUrl' ] );
	}

	/**
	 * Translates the logo attachment URL to the current language.
	 *
	 * @param string|mixed $logoURL
	 * 
	 * @return string
	 */
	public function filterLogoUrl( $logoURL ) {
		if ( $this->shouldTranslateMediaUrl( $logoURL ) ) {
			return apply_filters( 'wpml_media_url', $logoURL );
		}
		
		return $logoURL;
	}

	/**
	 *
	 * @param string|mixed $mediaURL
	 * 
	 * @return boolean
	 */
	private function shouldTranslateMediaUrl( $mediaURL ) {
		return defined( 'WPML_MEDIA_VERSION' )
			&& $mediaURL
			&& is_string( $mediaURL )
			&& $this->isNotDefaultLanguage();
	}

	/**
	 *
	 * @return boolean
	 */
	private function isNotDefaultLanguage() {
		return apply_filters( 'wpml_current_language', null ) !== apply_filters( 'wpml_default_language', null );
	}

}
