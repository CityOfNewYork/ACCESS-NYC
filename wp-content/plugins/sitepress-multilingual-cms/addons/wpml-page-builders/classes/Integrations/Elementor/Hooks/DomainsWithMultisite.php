<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;
use WPML\PB\Helper\LanguageNegotiation;

class DomainsWithMultisite implements \IWPML_Backend_Action {

	public function add_hooks() {
		if ( is_multisite() && LanguageNegotiation::isUsingDomains() ) {
			Hooks::onAction( 'elementor/editor/init' )
				->then( spreadArgs( [ $this, 'onElementorEditor' ] ) );
		}
	}

	public function onElementorEditor() {
		$isCurrentLangDifferentThanDefault = apply_filters( 'wpml_current_language', null ) !== apply_filters( 'wpml_default_language', null );

		if ( $isCurrentLangDifferentThanDefault ) {
			Hooks::onFilter( 'admin_url' )
				->then( spreadArgs( [ $this, 'filterUrl' ] ) );
		}
	}

	/**
	 * @param string $url The admin area URL.
	 */
	public function filterUrl( $url ) {
		$parsedUrl = wpml_parse_url( $url );

		if ( is_array( $parsedUrl ) && ! empty( $parsedUrl['host'] ) ) {
			return http_build_url( Obj::assoc( 'host', $_SERVER['HTTP_HOST'], $parsedUrl ) );
		}

		return $url;
	}

	private static function isUsingDomains() {
		return apply_filters( 'wpml_setting', [], 'language_domains' )
			&& constant( 'WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN' ) === (int) apply_filters( 'wpml_setting', 1, 'language_negotiation_type' );
	}
}
