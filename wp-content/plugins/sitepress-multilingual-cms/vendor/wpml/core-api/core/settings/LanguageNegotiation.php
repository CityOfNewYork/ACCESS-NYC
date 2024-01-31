<?php

namespace WPML\Settings;

class LanguageNegotiation {

	/**
	 * @return bool
	 */
	public static function isDomain() {
		return constant( 'WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN' ) === self::getType();
	}

	/**
	 * @return bool
	 */
	public static function isDir() {
		return constant( 'WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY' ) === self::getType();
	}

	/**
	 * @return bool
	 */
	public static function isParam() {
		return constant( 'WPML_LANGUAGE_NEGOTIATION_TYPE_PARAMETER' ) === self::getType();
	}

	/**
	 * @return int
	 */
	private static function getType() {
		global $sitepress;

		return (int) $sitepress->get_setting( 'language_negotiation_type' );
	}
}
