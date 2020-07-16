<?php
/**
 * Plugin Name: Advanced Custom Fields Multilingual
 * Description: Adds compatibility between WPML and Advanced Custom Fields | <a href="https://wpml.org/documentation/related-projects/translate-sites-built-with-acf/">Documentation</a>
 * Author: OnTheGoSystems
 * Plugin URI: https://wpml.org/
 * Author URI: http://www.onthegosystems.com/
 * Version: 1.7.1
 *
 * @package WPML\ACF
 */

$autoloader_dir = __DIR__ . '/vendor';
if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
	$autoloader = $autoloader_dir . '/autoload.php';
} else {
	$autoloader = $autoloader_dir . '/autoload_52.php';
}
require_once $autoloader;

$acfml_dependencies_factory = new WPML_ACF_Dependencies_Factory();

$acfml = new WPML_ACF( $acfml_dependencies_factory );
if ( $acfml ) {
	$acfml->init_worker();
}

add_action( 'admin_enqueue_scripts', 'acfml_enqueue_scripts' );

/**
 * Hooks the scripts and styles.
 */
function acfml_enqueue_scripts() {
	if ( is_admin() ) {
		wp_enqueue_script( 'acfml_js', plugin_dir_url( __FILE__ ) . 'assets/js/admin-script.js', array( 'jquery' ) );
		wp_enqueue_style( 'acfml_css', plugin_dir_url( __FILE__ ) . 'assets/css/admin-style.css' );
	}
}


