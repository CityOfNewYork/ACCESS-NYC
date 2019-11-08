<?php
/**
 * Load the shared OTGS UI library, on demand.
 *
 * =================
 * Usage
 * =================
 * $vendor_path = [ path to the root of your relative vendor directory housing this repository, no training slash ]
 * $vendor_url = [ URL of the root of your relative vendor directory housing this repository, no trailing slash ]
 * require_once( $vendor_path . '/otgs/ui/loader.php' );
 * otgs_ui_initialize( $vendor_path . '/otgs/ui', $vendor_url . '/otgs/ui' );
 *
 * =================
 * Restrictions
 * =================
 * - Assets are registered at init:1: doing it earlier will cause problems with core assets registered at init:0
 * - Their handles are stored in constants that you can use as dependencies, on assets registered after init:-100.
 *
 * @package otgs/ui
 */

/**
 * OTGS UI version - increase after every major update.
 */
$otg_ui_version = 107;

/**
 * =================
 * ||   WARNING   ||
 * =================
 *
 * DO NOT EDIT below this line.
 */

global $otg_ui_versions;

if ( ! isset( $otg_ui_versions ) ) {
	$otg_ui_versions = array();
}

if ( ! isset( $otg_ui_versions[ $otg_ui_version ] ) ) {
	// Initialize the path to this version.
	$otg_ui_versions[ $otg_ui_version ] = array(
		'path' => str_replace( '\\', '/', dirname( __FILE__ ) ),
	);
}


if ( ! function_exists( 'otgs_ui_initialize' ) ) {

	/**
	 * @param string $vendor_path Path to the root of your relative vendor directory housing this repository (no trailing slash).
	 * @param string $vendor_url  URL of the root of your relative vendor directory housing this repository, no trailing slash.
	 */
	function otgs_ui_initialize( $vendor_path, $vendor_url ) {
		global $otg_ui_versions;

		$vendor_path = str_replace( '\\', '/', $vendor_path );

		$vendor_path = untrailingslashit( $vendor_path );
		$vendor_url  = untrailingslashit( $vendor_url );

		// Save the url in the version with a matching path.
		foreach ( $otg_ui_versions as $version => $data ) {
			if ( $otg_ui_versions[ $version ]['path'] === $vendor_path ) {
				$otg_ui_versions[ $version ]['url'] = $vendor_url;
				break;
			}
		}
	}
}

if ( ! function_exists( 'otgs_ui_plugins_loaded' ) ) {
	/**
	 * Function hooked to the `plugins_loaded` action as early as possible.
	 */
	function otgs_ui_plugins_loaded() {
		global $otg_ui_versions;

		// Find the latest version.
		$latest = 0;
		foreach ( $otg_ui_versions as $version => $data ) {
			if ( $version > $latest ) {
				$latest = $version;
			}
		}

		if ( $latest > 0 ) {
			// Require all the available classes: we need to overcome autoloaders!!
			require_once $otg_ui_versions[ $latest ]['path'] . '/src/php/OTGS_Assets_Handles.php';
			require_once $otg_ui_versions[ $latest ]['path'] . '/src/php/OTGS_Assets_Store.php';
			require_once $otg_ui_versions[ $latest ]['path'] . '/src/php/OTGS_UI_Assets.php';
			require_once $otg_ui_versions[ $latest ]['path'] . '/src/php/OTGS_UI_Loader.php';

			// Initialize the assets loader with its assets and store dependencies.
			$assets_store = new OTGS_Assets_Store();
			$assets       = new OTGS_UI_Assets( $otg_ui_versions[ $latest ]['url'] . '/dist', $assets_store );
			$loader       = new OTGS_UI_Loader( $assets_store, $assets );
			$loader->load();
		}
	}

	add_action( 'plugins_loaded', 'otgs_ui_plugins_loaded', -PHP_INT_MAX );
}
