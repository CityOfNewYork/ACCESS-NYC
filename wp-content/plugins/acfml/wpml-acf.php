<?php
/**
 * Plugin Name: Advanced Custom Fields Multilingual
 * Description: Adds compatibility between WPML and Advanced Custom Fields | <a href="https://wpml.org/documentation/related-projects/translate-sites-built-with-acf/?utm_source=plugin&utm_medium=gui&utm_campaign=acfml">Documentation</a>
 * Author: OnTheGoSystems
 * Plugin URI: https://wpml.org/
 * Author URI: http://www.onthegosystems.com/
 * Version: 2.1.5
 *
 * @package WPML\ACF
 */

if ( get_option( '_wpml_inactive' ) ) {
	return;
}

// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
function acfmlInit() {
	$vendorDir = __DIR__ . '/vendor';

	if ( ! class_exists( 'WPML_Core_Version_Check' ) ) {
		require_once $vendorDir . '/wpml-shared/wpml-lib-dependencies/src/dependencies/class-wpml-core-version-check.php';
	}

	if ( ! WPML_Core_Version_Check::is_ok( __DIR__ . '/wpml-dependencies.json' ) ) {
		return;
	}

	require_once $vendorDir . '/autoload.php';

	define( 'ACFML_VERSION', '2.1.5' );
	define( 'ACFML_PLUGIN_PATH', __DIR__ );
	define( 'ACFML_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

	\WPML\Container\share( \ACFML\Container\Config::getSharedClasses() ); // @phpstan-ignore-line

	$acfml = \WPML\Container\make( WPML_ACF::class );

	// We know that wpml_loaded happens on plugins_loaded:1.
	// We know that acf/init happens at init:5 regardless of whether ACF is a standalone plugin or bundled by a theme.
	// We just need to hook here with a priority below 6 to catch CPTs and CTs registration.
	add_action( 'acf/init', [ $acfml, 'init_worker' ], 1 );

	add_action( 'admin_enqueue_scripts', function() {
		wp_enqueue_style( 'acfml_css', plugin_dir_url( __FILE__ ) . 'assets/css/admin-style.css', [], ACFML_VERSION );
	} );

	load_plugin_textdomain( 'acfml', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
function loadACFMLrequirements() {
	require_once __DIR__ . '/classes/class-wpml-acf-requirements.php';

	$requirements = new WPML_ACF_Requirements();
	$requirements->check_wpml_core();
}

add_action( 'wpml_loaded', 'acfmlInit' );

add_action( 'plugins_loaded', 'loadACFMLrequirements' );

require_once __DIR__ . '/classes/Notice/Links.php';
require_once __DIR__ . '/classes/Notice/Activation.php';
register_activation_hook( __FILE__, [ \ACFML\Notice\Activation::class, 'activate' ] );
