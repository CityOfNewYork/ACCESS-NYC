<?php

class WPML_Plugins_Check {
	/**
	 * @param string $bundle_json
	 * @param string $tm_version
	 * @param string $st_version
	 * @param string $wcml_version
	 */
	public static function disable_outdated(
		$bundle_json,
		$tm_version,
		$st_version,
		$wcml_version
	) {
		$required_versions = json_decode( $bundle_json, true );

		if ( version_compare( $st_version, $required_versions['wpml-string-translation'], '<' ) ) {
			remove_action( 'wpml_before_init', 'load_wpml_st_basics' );
		}

		if ( version_compare( $wcml_version, $required_versions['woocommerce-multilingual'], '<' ) ) {
			global $woocommerce_wpml;

			if ( $woocommerce_wpml ) {
				remove_action( 'wpml_loaded', [ $woocommerce_wpml, 'load' ] );
				remove_action( 'init', [ $woocommerce_wpml, 'init' ], 2 );
			}

			remove_action( 'wpml_loaded', 'wcml_loader' );
		}
	}
}
