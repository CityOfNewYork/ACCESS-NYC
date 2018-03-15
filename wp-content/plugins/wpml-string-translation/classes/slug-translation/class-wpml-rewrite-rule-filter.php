<?php

class WPML_Rewrite_Rule_Filter {

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var WPML_Slug_Translation_Records $slug_records */
	private $slug_records;

	public function __construct( WPML_Slug_Translation_Records $slug_records, SitePress $sitepress ) {
		$this->slug_records = $slug_records;
		$this->sitepress = $sitepress;
	}

	function rewrite_rules_filter( $value ) {
		if ( empty( $value ) ) {
			return $value;
		}

		$current_language               = $this->sitepress->get_current_language();
		$default_language               = $this->sitepress->get_default_language();
		$queryable_post_types           = get_post_types( array( 'publicly_queryable' => true ) );
		$post_slug_translation_settings = $this->sitepress->get_setting( 'posts_slug_translation', array() );

		foreach ( $queryable_post_types as $type ) {
			if ( ! isset( $post_slug_translation_settings['types'][ $type ] ) || ! $post_slug_translation_settings['types'][ $type ] || ! $this->sitepress->is_translated_post_type( $type ) ) {
				continue;
			}
			$slug = $this->get_slug_by_type( $type );
			if ( $slug === false ) {
				continue;
			}

			$display_as_translated_mode = $this->sitepress->is_display_as_translated_post_type( $type );

			$slug_translation = $this->slug_records->get_translation( $type, $current_language );
			if ( ! $slug_translation ) {
				// check original
				$slug_translation = $this->slug_records->get_original( $type, $current_language );
			}
			if ( $display_as_translated_mode && ( ! $slug_translation || $slug_translation === $slug ) && $default_language != 'en' ) {
				$slug_translation = $this->slug_records->get_translation( $type, $default_language );
			}
			$slug_translation = trim( $slug_translation, '/' );

			$using_tags = false;
			/* case of slug using %tags% - PART 1 of 2 - START */
			if ( preg_match( '#%([^/]+)%#', $slug ) ) {
				$slug       = preg_replace( '#%[^/]+%#', '.+?', $slug );
				$using_tags = true;
			}
			if ( preg_match( '#%([^/]+)%#', $slug_translation ) ) {
				$slug_translation = preg_replace( '#%[^/]+%#', '.+?', $slug_translation );
				$using_tags       = true;
			}
			/* case of slug using %tags% - PART 1 of 2 - END */

			$buff_value = array();
			foreach ( (array) $value as $match => $query ) {

				if ( $slug && $slug != $slug_translation ) {
					$new_match = $this->adjust_key( $match, $slug_translation, $slug );
					$buff_value[ $new_match ] = $query;
					if ( $new_match != $match && $display_as_translated_mode ) {
						$buff_value[ $match ] = $query;
					}
				} else {
					$buff_value[ $match ] = $query;
				}
			}

			$value = $buff_value;
			unset( $buff_value );

			/* case of slug using %tags% - PART 2 of 2 - START */
			if ( $using_tags ) {
				if ( preg_match( '#\.\+\?#', $slug ) ) {
					$slug = preg_replace( '#\.\+\?#', '(.+?)', $slug );
				}
				if ( preg_match( '#\.\+\?#', $slug_translation ) ) {
					$slug_translation = preg_replace( '#\.\+\?#', '(.+?)', $slug_translation );
				}
				$buff_value = array();
				foreach ( $value as $match => $query ) {
					if ( trim( $slug ) && trim( $slug_translation ) && $slug != $slug_translation ) {
						$match = $this->adjust_key( $match, $slug_translation, $slug );
					}
					$buff_value[ $match ] = $query;
				}

				$value = $buff_value;
				unset( $buff_value );
			}
			/* case of slug using %tags% - PART 2 of 2 - END */
		}

		return $value;
	}

	function get_slug_by_type( $type ) {
		return $this->slug_records->get_original( $type );
	}

	private function adjust_key( $k, $slug_translation, $slug ) {
		if ( (bool) $slug_translation === true && preg_match( '#^[^/]*/?' . preg_quote( $slug ) . '/#',
				$k ) && $slug != $slug_translation
		) {
			$k = preg_replace( '#^' . addslashes($slug) . '/#', $slug_translation . '/', $k );
		}

		return $k;
	}
}