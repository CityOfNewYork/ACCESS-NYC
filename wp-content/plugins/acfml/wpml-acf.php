<?php
/**
 * Plugin Name: Advanced Custom Fields Multilingual
 * Description: Adds compatibility between WPML and Advanced Custom Fields | <a href="https://wpml.org/documentation/related-projects/translate-sites-built-with-acf/?utm_source=plugin&utm_medium=gui&utm_campaign=acfml">Documentation</a>
 * Author: OnTheGoSystems
 * Plugin URI: https://wpml.org/
 * Author URI: http://www.onthegosystems.com/
 * Version: 1.10.3
 *
 * @package WPML\ACF
 */

if ( get_option( '_wpml_inactive' ) ) {
	return;
}

function acfmlInit() {
	$vendorDir = __DIR__ . '/vendor';

	if ( ! class_exists( 'WPML_Core_Version_Check' ) ) {
		require_once $vendorDir . '/wpml-shared/wpml-lib-dependencies/src/dependencies/class-wpml-core-version-check.php';
	}

	if ( ! WPML_Core_Version_Check::is_ok( __DIR__ . '/wpml-dependencies.json' ) ) {
		return;
	}

	require_once $vendorDir . '/autoload.php';

	$acfml_dependencies_factory = new WPML_ACF_Dependencies_Factory();
	$acfml = new WPML_ACF( $acfml_dependencies_factory );
	if ( did_action( 'acf/init' ) ) {
		$acfml->init_worker();
	} else {
		add_action( 'acf/init', [ $acfml, 'init_worker' ] );
	}

	add_action( 'admin_enqueue_scripts', function() {
		wp_enqueue_script( 'acfml_js', plugin_dir_url( __FILE__ ) . 'assets/js/admin-script.js', array( 'jquery' ) );
		wp_enqueue_style( 'acfml_css', plugin_dir_url( __FILE__ ) . 'assets/css/admin-style.css' );
	} );
}

function loadACFMLrequirements() {
	require_once __DIR__ . '/classes/class-wpml-acf-requirements.php';

	$requirements = new WPML_ACF_Requirements();
	$requirements->check_wpml_core();
}

add_action( 'wpml_loaded', 'acfmlInit' );

add_action( 'plugins_loaded', 'loadACFMLrequirements' );
