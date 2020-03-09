<?php
/**
 * Plugin Name: Advanced Custom Fields Multilingual
 * Description: This 'glue' plugin makes it easier to translate with WPML content provided in fields created with Advanced Custom Fields
 * Author: OnTheGoSystems
 * Plugin URI: https://wpml.org/
 * Author URI: http://www.onthegosystems.com/
 * Version: 1.5.0
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


