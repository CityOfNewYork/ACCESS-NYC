<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\LIB\WP\Hooks;
use WPML\PB\Helper\LanguageNegotiation;

use function WPML\FP\spreadArgs;

class CustomFonts implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	public function add_hooks() {
		if ( LanguageNegotiation::isUsingDomains() ) {
			Hooks::onFilter( 'option_elementor_fonts_manager_fonts' )
				->then( spreadArgs( [ $this, 'replaceUrls' ] ) );
		}
	}

	/**
	 * @param array[] $fonts
	 *
	 * @return array[]
	 */
	public function replaceUrls( $fonts ) {
		$defaultLanguage = apply_filters( 'wpml_default_language', false );
		$currentLanguage = apply_filters( 'wpml_current_language', false );

		return wpml_collect( $fonts )
			->map( function ( $font ) use ( $defaultLanguage, $currentLanguage ) {
				$font['font_face'] = str_replace(
					LanguageNegotiation::getDomainByLanguage( $defaultLanguage ),
					LanguageNegotiation::getDomainByLanguage( $currentLanguage ),
					$font['font_face']
				);

				return $font;
			} )
			->all();
	}
}
