<?php

/**
 * Plugin Name: Rollbar PHP Wordpress Must Use Plugin
 * Plugin URI: https://github.com/rollbar/rollbar-php-wordpress
 * Description: "Must-use" proxy plugin for Rollbar PHP Wordpress. 
 * Author:          Rollbar
 * Author URI:      https://rollbar.com
 */
 
$rollbar_plugin = __DIR__ . '/../plugins/rollbar/rollbar-php-wordpress.php';

if ( ! file_exists( $rollbar_plugin ) ) {
	return;
}

require $rollbar_plugin;