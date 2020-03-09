<?php

namespace WPML\ST\Gettext\Filters;

class StringTracking implements IFilter {

	/**
	 * @param string       $translation
	 * @param string       $text
	 * @param string|array $domain
	 * @param string|false $name
	 *
	 * @return string
	 */
	public function filter( $translation, $text, $domain, $name = false ) {

		if ( $this->canTrackStrings() ) {
			icl_st_track_string( $text, $domain, ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE );
		}

		return $translation;
	}

	/**
	 * @return bool
	 */
	public function canTrackStrings() {
		return did_action( 'after_setup_theme' );
	}
}
