<?php

namespace WPML\LIB\WP\App;

use WPML\LIB\WP\Nonce;
use WPML\LIB\WP\WordPress;

class Resources {

	/**
	 * Enqueue a JavaScript application file from the dist directory.
	 *
	 * @param string      $app
	 * @param string      $pluginBaseUrl
	 * @param string      $pluginBasePath
	 * @param string      $version
	 * @param null|string $domain
	 * @param null|array  $localize
	 */
	public static function enqueue( $app, $pluginBaseUrl, $pluginBasePath, $version, $domain = null, $localize = null ) {
		$handle = "wpml-$app-ui";

		wp_register_script(
			$handle,
			"$pluginBaseUrl/dist/js/$app/app.js",
			[],
			$version
		);

		if ( $localize ) {
			if ( isset( $localize['data']['endpoint'] ) ) {
				$localize['data']['nonce'] = Nonce::create( $localize['data']['endpoint'] );
			}
			wp_localize_script( $handle, $localize['name'], $localize['data'] );
		}

		wp_enqueue_script( $handle );

		wp_enqueue_style(
			$handle,
			"$pluginBaseUrl/dist/css/$app/styles.css",
			[],
			$version
		);

		if ( $domain && WordPress::versionCompare( '>=', '5.0.0' ) ) {
			wp_set_script_translations( $handle, $domain, "$pluginBasePath/locale/jed/$handle" );
		}
	}
}
