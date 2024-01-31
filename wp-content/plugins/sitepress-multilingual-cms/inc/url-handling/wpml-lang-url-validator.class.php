<?php

class WPML_Lang_URL_Validator {

	/** @var  SitePress $sitepress */
	private $sitepress;

	/** @var WPML_URL_Converter $wpml_url_converter */
	private $url_converter;

	/**
	 * @param WPML_URL_Converter $wpml_url_converter
	 * @param SitePress          $sitepress
	 */
	public function __construct( WPML_URL_Converter $wpml_url_converter, SitePress $sitepress ) {
		$this->sitepress     = $sitepress;
		$this->url_converter = $wpml_url_converter;
	}

	public function validate_langs_in_dirs() {
		return get_option( 'permalink_structure', false );
	}

	public function print_explanation( $sample_lang_code, $root = false ) {
		$def_lang_code = $this->sitepress->get_default_language();
		$sample_lang   = $this->sitepress->get_language_details( $sample_lang_code );
		$def_lang      = $this->sitepress->get_language_details( $def_lang_code );
		$output        = '<span class="explanation-text">(';

		if ( $def_lang ) {
			$output .= sprintf(
				'%s - %s, ',
				trailingslashit( $this->get_sample_url( $root ? $def_lang_code : '' ) ),
				esc_html( $def_lang['display_name'] )
			);
		}

		$output .= sprintf(
			'%s - %s',
			trailingslashit( $this->get_sample_url( $sample_lang_code ) ),
			esc_html( $sample_lang['display_name'] )
		);
		$output .= ')</span>';

		return $output;
	}

	private function get_sample_url( $sample_lang_code ) {
		$abs_home = $this->url_converter->get_abs_home();

		return untrailingslashit( trailingslashit( $abs_home ) . $sample_lang_code );
	}
}
