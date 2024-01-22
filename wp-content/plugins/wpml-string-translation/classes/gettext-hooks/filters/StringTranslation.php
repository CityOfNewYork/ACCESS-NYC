<?php

namespace WPML\ST\Gettext\Filters;

use WPML\ST\Gettext\Settings;

class StringTranslation implements IFilter {

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
		if ( $this->settings->isDomainRegistrationExcluded( $domain ) ) {
			return $translation;
		}

		if ( ! defined( 'ICL_STRING_TRANSLATION_DYNAMIC_CONTEXT' ) ) {
			define( 'ICL_STRING_TRANSLATION_DYNAMIC_CONTEXT', 'wpml_string' );
		}

		if ( $domain === ICL_STRING_TRANSLATION_DYNAMIC_CONTEXT ) {
			icl_register_string( $domain, (string) $name, $text );
		}

		$has_translation = null;

		$found_translation = icl_translate( $domain, (string) $name, $text, false, $has_translation );

		if ( $has_translation ) {
			return $found_translation;
		} else {
			return $translation;
		}
	}
}
