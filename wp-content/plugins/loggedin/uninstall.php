<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://duckdev.com/products/loggedin-limit-active-logins/
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 * @category   Core
 * @package    Loggedin
 * @subpackage Uninstall
 * @author     Joel James <me@joelsays.com>
 */

// If uninstall not called from WordPress, then exit.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Delete all options added by the plugin.
delete_option( 'loggedin_maximum' );
delete_option( 'loggedin_rating_notice' );

global $wpdb;

// Delete all meta values added by the plugin.
// phpcs:ignore
$wpdb->delete(
	$wpdb->usermeta,
	array(
		// Review notice meta.
		// phpcs:ignore
		'meta_key' => 'loggedin_rating_notice_dismissed',
	)
);
