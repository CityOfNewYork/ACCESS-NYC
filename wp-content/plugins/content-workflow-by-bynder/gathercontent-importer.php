<?php
/**
 * Plugin Name:  Content Workflow (by Bynder)
 * Description:  Imports items from Content Workflow to your Wordpress site
 * Version:      1.0.5
 * Author:       Content Workflow (by Bynder)
 * Requires PHP: 7.0
 * Author URI:   https://www.bynder.com/products/content-workflow/
 * Text Domain:  content-workflow
 * Domain Path:  /languages
 * License:      GPL-2.0+
 */

/**
 * Copyright (c) 2016 GatherContent (email : support@gathercontent.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Useful global constants
define( 'GATHERCONTENT_VERSION', '1.0.5' );
define( 'GATHERCONTENT_ENQUEUE_VERSION', '1.0.5' );
define( 'GATHERCONTENT_SLUG', 'content-workflow' );
define( 'GATHERCONTENT_PLUGIN', __FILE__ );
define( 'GATHERCONTENT_URL', plugin_dir_url( __FILE__ ) );
define( 'GATHERCONTENT_PATH', dirname( __FILE__ ) . '/' );
define( 'GATHERCONTENT_INC', GATHERCONTENT_PATH . 'includes/' );

if ( version_compare( phpversion(), '7.0', '<' ) ) {

	// Womp womp.. PHP needs to be updated!
	add_action( 'all_admin_notices', 'cwby_importer_php_version_too_low_notice' );

} elseif ( version_compare( $GLOBALS['wp_version'], '5.8.2', '<' ) ) {

	// Sad Trombone.. WordPress needs to be updated!
	add_action( 'all_admin_notices', 'cwby_importer_wp_version_too_low_notice' );
} else {

	// Include files
	require_once GATHERCONTENT_INC . 'functions/core.php';
	require_once GATHERCONTENT_INC . 'functions/functions.php';
}

/**
 * If the server does not have the minimum supported version of PHP,
 * this notice will be shown in the dashboard.
 *
 * @return void
 * @since  3.0.0
 *
 */
function cwby_importer_php_version_too_low_notice() {
	$message = esc_html__( 'Sorry, the Content Workflow plugin requires a minimum PHP version of 5.3. Please contact your host and ask them to upgrade. For convenience, you can use the note provided on the WordPress recommended host supports page: ', 'content-workflow-by-bynder' );

	echo '<div id="message" class="error">
		<p> ' .
	     esc_html($message) .
	     '<a href="https://wordpress.org/about/requirements/">https://wordpress.org/about/requirements/</a>' .
	     '</p>
	</div>';

}

/**
 * If the version of WordPress is not supported, this notice will be shown in the dashboard.
 *
 * @return void
 * @since  3.0.0
 *
 */
function cwby_importer_wp_version_too_low_notice() {
	printf(
		'<div id="message" class="error"><p>%s</p></div>',
		esc_html__( 'Sorry, for security and performance reasons, the Content Workflow plugin requires a minimum WordPress version of 4.4. Please update WordPress to the most recent version.', 'content-workflow-by-bynder' )
	);
}

/**
 * Registers the default textdomain.
 *
 * @return void
 * @uses apply_filters()
 * @uses get_locale()
 * @uses load_textdomain()
 * @uses load_plugin_textdomain()
 * @uses plugin_basename()
 *
 * @since  3.0.0
 *
 */
function cwby_importer_i18n() {
	$text_domain = GATHERCONTENT_SLUG;
	$locale      = apply_filters( 'plugin_locale', get_locale(), $text_domain );
	load_textdomain( $text_domain, WP_LANG_DIR . "/{$text_domain}/{$text_domain}-{$locale}.mo" );
	load_plugin_textdomain( $text_domain, false, plugin_basename( GATHERCONTENT_PATH ) . '/languages/' );
}

add_action( 'init', 'cwby_importer_i18n' );
