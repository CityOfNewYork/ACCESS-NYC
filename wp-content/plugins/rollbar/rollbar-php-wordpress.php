<?php
/**
 * Plugin Name:     Rollbar
 * Plugin URI:      https://wordpress.org/plugins/rollbar
 * Description:     Rollbar full-stack error tracking for WordPress
 * Version:         2.6.1
 * Author:          Rollbar
 * Author URI:      https://rollbar.com
 * Text Domain:     rollbar
 *
 * @package         Rollbar\Wordpress
 * @author          flowdee,arturmoczulski
 * @copyright       Rollbar, Inc.
 */
 
namespace Rollbar\Wordpress;

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

/*
 * Libs
 * 
 * The included copy of rollbar-php is only going to be loaded if the it has
 * not been loaded through Composer yet.
 */
if( !class_exists('Rollbar\Rollbar') || !class_exists('Rollbar\Wordpress\Plugin') ) {
    require_once \plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}

\Rollbar\Wordpress\Plugin::load();
