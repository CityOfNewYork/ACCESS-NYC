<?php

/**
 * WPML Page Builders can be installed as a standalone glue plugin,
 * but it also comes packaged with WPML Core.
 *
 * To include it on WPML Core, do as follows:
 * - Include this repository as a Composer dependency.
 * - Wait until plugins_loaded to include this loader.php file.
 *
 * This will ensure that the glue plugin can be used if available;
 * otherwise, this will ensure that the WPML plugin packing the newest version will push it.
 *
 * $wpml_page_builders_version must be increased on every new version of the glue plugin.
 * Also, having a negative priority ensures that the highest version number gets called first.
 */

/**
 * WARNING: INCREASE THIS LOADER VERSION ON EVERY NEW RELEASE.
 */
$wpml_page_builders_version = 21;

add_action( 'init', function() use ( $wpml_page_builders_version ) {
	if ( defined( 'WPML_PAGE_BUILDERS_LOADED' ) ) {
		// A more recent version of WPML Page Builders is already active.
		return;
	}

	// Define WPML_PAGE_BUILDERS_LOADED so any older instance of WPML Page Builders is not loaded.
	define( 'WPML_PAGE_BUILDERS_LOADED', $wpml_page_builders_version );

	require_once __DIR__ . '/app.php';

}, 1 - $wpml_page_builders_version );
