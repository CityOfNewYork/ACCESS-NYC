<?php
/**
 * Get value from default mo file
 *
 * @package WPML\ST
 */
namespace WPML\ST\WpSettings;

use MO;

class DefaultMO {
	/**
	 * Get default translation
	 *
	 * @param string $name - string name.
	 * @param string $domain - i18n domain.
	 * @param string $locale - i18n locale.
	 *
	 * @return string
	 */
	public function translate( $name, $domain, $locale ) {
		$file_base = 'default' === $domain ? $locale : $domain . '-' . $locale;
		$mo_file   = WP_LANG_DIR . '/' . $file_base . '.mo';

		if ( ! file_exists( $mo_file ) ) {
			return '';
		}

		$mo = new MO();
		if ( $mo->import_from_file( $mo_file ) ) {
			return $this->get_default_translation_value( $mo, $name );
		} else {
			return '';
		}
	}

	/**
	 * Get WP default date/time format value
	 *
	 * @param \MO    $mo - MO files worker.
	 * @param string $name - string name of date/time format.
	 *
	 * @return string
	 */
	private function get_default_translation_value( $mo, $name ) {
		switch ( $name ) {
			case DateTimeFormatsDefaultLocaleValues::STRING_NAME_DATE_FORMAT:
				// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction
				return $mo->translate( 'F j, Y' );
			case DateTimeFormatsDefaultLocaleValues::STRING_NAME_TIME_FORMAT:
				// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction
				return $mo->translate( 'g:i a' );
			case DateTimeFormatsDefaultLocaleValues::STRING_NAME_LINKS_UPDATED_DATE_FORMAT:
				// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction
				return $mo->translate( 'F j, Y g:i a' );
			default:
				return '';
		}
	}
}
