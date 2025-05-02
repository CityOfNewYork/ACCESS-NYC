<?php

namespace WPML\ST\Gettext\Filters;

use WPML\ST\Gettext\HooksFactory;
use WPML\ST\Gettext\Settings;

class StringHighlighting implements IFilter {

	const HIGHLIGHT_ID_TO_REPLACE_IN_HTML_START = 'WPMLHIGHLIGHTSTRINGSTART';
	const HIGHLIGHT_ID_TO_REPLACE_IN_HTML_END = 'WPMLHIGHLIGHTSTRINGEND';

	/** @var Settings $settings */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @param string       $translation
	 * @param string       $text
	 * @param string|array $domain
	 * @param string|false $name
	 *
	 * @return string
	 */
	public function filter( $translation, $text, $domain, $name = false ) {
		if ( is_array( $domain ) ) {
			$domain = $domain['domain'];
		}

		if ( $this->isHighlighting( $domain, $text ) ) {
			$translation = self::HIGHLIGHT_ID_TO_REPLACE_IN_HTML_START
				. $translation
				. self::HIGHLIGHT_ID_TO_REPLACE_IN_HTML_END;
		}

		return $translation;
	}

	/**
	 * @param string $domain
	 * @param string $text
	 *
	 * @return bool
	 */
	private function isHighlighting( $domain, $text ) {
		return isset( $_GET[ HooksFactory::TRACK_PARAM_TEXT ], $_GET[ HooksFactory::TRACK_PARAM_DOMAIN ] )
			   && stripslashes( $_GET[ HooksFactory::TRACK_PARAM_DOMAIN ] ) === $domain
			   && stripslashes( $_GET[ HooksFactory::TRACK_PARAM_TEXT ] ) === $text;
	}
}
