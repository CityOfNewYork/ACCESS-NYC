<?php

/**
 *
 * @link              https://bitly.com/
 * @since             2.6.0
 * @package           Wp_Bitly
 *
 * @wordpress-plugin
 * Plugin Name:       Bitly's Wordpress Plugin
 * Plugin URI:        https://wordpress.org/plugins/wp-bitly/
 * Description:       WP Bitly can be used to generate shortlinks for your website posts, pages, and custom post types. Extremely lightweight and easy to set up!
 * Version:           2.6.0
 * Author:            Bitly
 * Author URI:        https://bitly.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-bitly
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WPBITLY_VERSION', '2.6.0' );


define('WPBITLY_DIR', WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__)));
define('WPBITLY_URL', plugins_url() . '/' . basename(dirname(__FILE__)));

define('WPBITLY_LOG', WPBITLY_DIR . '/log/debug.txt');
define('WPBITLY_ERROR', __('WP Bitly Error: No such option %1$s', 'wp-bitly'));

define('WPBITLY_OPTIONS', 'wpbitly-options');
define('WPBITLY_AUTHORIZED', 'wpbitly-authorized');

define('WPBITLY_BITLY_API', 'https://api-ssl.bitly.com/v4/');
define('WPBITLY_OAUTH_API', 'https://bitly.com/oauth/authorize');

define('WPBITLY_OAUTH_CLIENT_ID', '7a259846da22b485c711c5bc3a31ac83290aae99');

define('WPBITLY_OAUTH_REDIRECT_URI', 'urn:ietf:wg:oauth:2.0:oob:auto');


function activate_wp_bitly() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-bitly-activator.php';
	Wp_Bitly_Activator::activate();
}

function deactivate_wp_bitly() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-bitly-deactivator.php';
	Wp_Bitly_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_bitly' );
register_deactivation_hook( __FILE__, 'deactivate_wp_bitly' );




/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-bitly.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    2.6.0
 */
function run_wp_bitly() {

	$plugin = new Wp_Bitly();
	$plugin->run();

}
run_wp_bitly();
