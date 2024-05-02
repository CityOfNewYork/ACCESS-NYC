<?php

namespace ACFML\Notice;

use WPML\FP\Obj;
use WPML\FP\Str;

class Links {

	// ACFML
	const DOC_ACFML_MAIN             = 'https://wpml.org/documentation/related-projects/translate-sites-built-with-acf/';
	const DOC_ACFML_EXPERT           = 'https://wpml.org/documentation/related-projects/translate-sites-built-with-acf/expert-translation-option/';
	const DOC_ACFML_TRANSLATE_LABELS = 'https://wpml.org/documentation/related-projects/translate-sites-built-with-acf/translating-acf-field-labels-with-wpml/';

	// General
	const DOC_DIFFERENT_TRANSLATION_EDITORS = 'https://wpml.org/documentation/translating-your-contents/using-different-translation-editors-for-different-pages/';
	const DOC_TRANSLATE_POST_TYPE           = 'https://wpml.org/documentation/getting-started-guide/translating-custom-posts/';
	const FAQ_INSTALL_ST                    = 'https://wpml.org/faq/how-to-add-string-translation-to-your-site/';

	/**
	 * @param string $link   Link.
	 * @param array  $params UTM parameters.
	 *
	 * @return string
	 */
	private static function generate( $link, $params = [] ) {
		$anchor = Obj::prop( 'anchor', $params );

		$utmTags = wpml_collect( [
			'utm_source'   => 'plugin',
			'utm_medium'   => 'gui',
			'utm_campaign' => 'acfml',
			] )
			->merge( $params )
			->filter( function( $value, $key ) {
				return Str::startsWith( 'utm_', $key );
			} )
			->toArray();

		return add_query_arg( $utmTags, $anchor ? $link . '#' . $anchor : $link );
	}

	/**
	 * @param array $params
	 *
	 * @return string
	 */
	public static function getAcfmlMainDoc( $params = [] ) {
		return self::generate( self::DOC_ACFML_MAIN, $params );
	}

	/**
	 * @param array $params
	 *
	 * @return string
	 */
	public static function getAcfmlMainModeTranslationDoc( $params = [] ) {
		return self::getAcfmlMainDoc( array_merge( $params, [ 'anchor' => 'using-same-fields-across-languages' ] ) );
	}

	/**
	 * @param array $params
	 *
	 * @return string
	 */
	public static function getAcfmlMainModeLocalizationDoc( $params = [] ) {
		return self::getAcfmlMainDoc( array_merge( $params, [ 'anchor' => 'using-different-fields-across-languages' ] ) );
	}

	/**
	 * @param array $params
	 *
	 * @return string
	 */
	public static function getAcfmlExpertDoc( $params = [] ) {
		return self::generate( self::DOC_ACFML_EXPERT, $params );
	}
	/**
	 * @param string $anchor
	 *
	 * @return string
	 */
	public static function getAcfmlTranslateLabels( $anchor = '' ) {
		return self::generate( self::DOC_ACFML_TRANSLATE_LABELS, [ 'anchor' => $anchor ] );
	}

	/**
	 * @param array $params
	 *
	 * @return string
	 */
	public static function getDifferentTranslationEditorsDoc( $params = [] ) {
		return self::generate( self::DOC_DIFFERENT_TRANSLATION_EDITORS, $params );
	}

	/**
	 * @return string
	 */
	public static function getFaqInstallST() {
		return self::generate( self::FAQ_INSTALL_ST );
	}

	/**
	 * @return string
	 */
	public static function getDocTranslatePostType() {
		return self::generate( self::DOC_TRANSLATE_POST_TYPE );
	}
}
