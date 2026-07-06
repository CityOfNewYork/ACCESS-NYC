<?php
/**
 * Plugin file for WP Bitly.
 *
 * @link              https://bitly.com/
 * @since             2.6.0
 * @package           Wp_Bitly
 *
 * @wordpress-plugin
 * Plugin Name:       Bitly's WordPress Plugin
 * Plugin URI:        https://wordpress.org/plugins/wp-bitly/
 * Description:       WP Bitly can be used to generate shortlinks for your website posts, pages, and custom post types. Extremely lightweight and easy to set up!
 * Version:           2.8.1
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

define( 'WPBITLY_VERSION', '2.8.1' );


define( 'WPBITLY_DIR', WP_PLUGIN_DIR . '/' . basename( __DIR__ ) );
define( 'WPBITLY_URL', plugins_url() . '/' . basename( __DIR__ ) );

// translators: %1$s is the option name that was not found.
define( 'WPBITLY_ERROR', esc_attr__( 'WP Bitly Error: No such option %1$s', 'wp-bitly' ) );

define( 'WPBITLY_OPTIONS', 'wpbitly-options' );
define( 'WPBITLY_AUTHORIZED', 'wpbitly-authorized' );

define( 'WPBITLY_BITLY_API', 'https://api-ssl.bitly.com' );
define( 'WPBITLY_OAUTH_API', 'https://bitly.com/oauth/authorize' );
define( 'WBBITLY_SSL_VERIFY', true );

define( 'WPBITLY_OAUTH_CLIENT_ID', '7a259846da22b485c711c5bc3a31ac83290aae99' );

define( 'WPBITLY_OAUTH_REDIRECT_URI', 'urn:ietf:wg:oauth:2.0:oob:auto' );


/**
 * Run during plugin activation.
 *
 * @since 2.6.0
 */
function activate_wp_bitly() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-bitly-activator.php';
	Wp_Bitly_Activator::activate();
}

/**
 * Run during plugin deactivation.
 *
 * @since 2.6.0
 */
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
