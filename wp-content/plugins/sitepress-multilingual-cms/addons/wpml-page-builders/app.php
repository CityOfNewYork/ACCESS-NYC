<?php

if ( ! defined( 'WPML_PAGE_BUILDERS_LOADED' ) ) {
	throw new Exception( 'This file should be called from the loader only.' );
}

require_once __DIR__ . '/classes/OldPlugin.php';
if ( WPML\PB\OldPlugin::handle() ) {
	return;
}

define( 'WPML_PAGE_BUILDERS_VERSION', '2.0.3' );
define( 'WPML_PAGE_BUILDERS_PATH', __DIR__ );

if ( ! class_exists( 'WPML_Core_Version_Check' ) ) {
	require_once WPML_PAGE_BUILDERS_PATH . '/vendor/wpml-shared/wpml-lib-dependencies/src/dependencies/class-wpml-core-version-check.php';
}

if ( ! WPML_Core_Version_Check::is_ok( WPML_PAGE_BUILDERS_PATH . '/wpml-dependencies.json' ) ) {
	return;
}

require_once WPML_PAGE_BUILDERS_PATH . '/vendor/autoload.php';

\WPML\PB\App::run();