<?php

use WPML\Element\API\Languages;
class WPML_ST_Slug_Translations {
	/**
	 * @param WPML_ST_Slug $slug
	 * @param bool         $display_as_translated_mode
	 *
	 * @return string
	 */
	public function get( WPML_ST_Slug $slug, $display_as_translated_mode ) {
		$slug_translation = $this->get_slug_translation_to_lang( $slug, Languages::getCurrentCode() );

		if ( ! $slug_translation ) {
			$default_language = Languages::getDefaultCode();
			if ( $display_as_translated_mode && $default_language != 'en' ) {
				$slug_translation = $this->get_slug_translation_to_lang( $slug, $default_language );
			} else {
				$slug_translation = $slug->get_original_value();
			}
		}

		return $slug_translation ? trim( $slug_translation, '/' ) : '';
	}

	/**
	 * @param WPML_ST_Slug $slug
	 * @param string $lang
	 *
	 * @return string|null
	 */
	private function get_slug_translation_to_lang( WPML_ST_Slug $slug, $lang ) {
		if ( $slug->is_translation_complete( $lang ) ) {
			return $slug->get_value( $lang );
		}

		return null;
	}
}
