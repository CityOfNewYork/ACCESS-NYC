<?php
/**
 * Plugin Name:     Loggedin - Limit Active Logins
 * Plugin URI:      https://duckdev.com/products/loggedin-limit-active-logins/
 * Description:     Light weight plugin to limit number of active logins from an account. Set maximum number of concurrent logins a user can have from multiple places.
 * Version:         1.2.0
 * Author:          Joel James
 * Author URI:      https://duckdev.com/
 * Donate link:     https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=XUVWY8HUBUXY4
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     loggedin
 * Domain Path:     /languages
 *
 * LoggedIn is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * LoggedIn is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with LoggedIn. If not, see <http://www.gnu.org/licenses/>.
 */
// If this file is called directly, abort.
defined( 'WPINC' ) || die( 'Well, get lost.' );

// Only when class not already exist.
if ( ! class_exists( 'Loggedin' ) ) {
	// Load text domain.
	load_plugin_textdomain(
		'loggedin',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages/'
	);

	// Only execute if not admin side.
	if ( ! is_admin() ) {
		require dirname( __FILE__ ) . '/includes/class-loggedin.php';
		new Loggedin();
	}

	// Only execute if admin side
	if ( is_admin() ) {
		require dirname( __FILE__ ) . '/includes/class-loggedin-admin.php';
		new Loggedin_Admin();
	}
}

//*** Thank you for your interest in LoggedIn - Developed and managed by Joel James ***//
