<?php

class WPML_ST_Slug_Translations {
	/** @var SitePress */
	private $sitepress;

	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/**
	 * @param WPML_ST_Slug $slug
	 * @param bool         $display_as_translated_mode
	 *
	 * @return string
	 */
	public function get( $slug, $display_as_translated_mode ) {
		$current_language = $this->sitepress->get_current_language();
		$default_language = $this->sitepress->get_default_language();
		$slug_translation = $this->get_slug_translation_to_lang( $slug, $current_language );

		if ( ! $slug_translation ) {
			// check original
			$slug_translation = $slug->get_original_value();
		}

		if ( $display_as_translated_mode && ( ! $slug_translation || $slug_translation === $slug->get_original_value() ) && $default_language != 'en' ) {
			$slug_translation = $this->get_slug_translation_to_lang( $slug, $default_language );
		}

		return trim( $slug_translation, '/' );
	}

	private function get_slug_translation_to_lang( WPML_ST_Slug $slug, $lang ) {
		if ( $slug->is_translation_complete( $lang ) ) {
			return $slug->get_value( $lang );
		}

		return null;
	}
}