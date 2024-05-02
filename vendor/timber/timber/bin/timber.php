<?php
/*
Plugin Name: Timber
Description: The WordPress Timber Library allows you to write themes using the power of Twig templates.
Plugin URI: https://upstatement.com/timber
Author: Timber Team & Contributors
Version: 1.23.0
Author URI: http://upstatement.com/
Requires PHP: 7.2.5
Requires at least: 5.3.0
*/
// we look for Composer files first in the plugins dir.
// then in the wp-content dir (site install).
// and finally in the current themes directories.
if ( file_exists( $composer_autoload = __DIR__ . '/vendor/autoload.php' ) /* check in self */
	|| file_exists( $composer_autoload = WP_CONTENT_DIR.'/vendor/autoload.php') /* check in wp-content */
	|| file_exists( $composer_autoload = plugin_dir_path( __FILE__ ).'vendor/autoload.php') /* check in plugin directory */
	|| file_exists( $composer_autoload = get_stylesheet_directory().'/vendor/autoload.php') /* check in child theme */
	|| file_exists( $composer_autoload = get_template_directory().'/vendor/autoload.php') /* check in parent theme */
) {
	require_once $composer_autoload;
}
new \Timber\Timber;
