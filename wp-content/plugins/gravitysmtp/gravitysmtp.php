<?php

/*
Plugin Name: Gravity SMTP
Plugin URI: https://gravityforms.com
Description: Confidently send emails from your website with secure and reliable SMTP providers and API-based services.
Version: 1.9.5
Author: Gravity Forms
Author URI: https://gravityforms.com
License: GPL-3.0+
Text Domain: gravitysmtp
Domain Path: /languages
------------------------------------------------------------------------
Copyright 2023 Rocketgenius Inc.
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program.  If not, see http://www.gnu.org/licenses.
*/

defined( 'ABSPATH' ) || die();

// Defines the current version of Gravity SMTP.
define( 'GF_GRAVITY_SMTP_VERSION', '1.9.5' );

define( 'GF_GRAVITY_SMTP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Path to GRAVITY_SMTP root folder.
 *
 * @since 1.0
 */
define( 'GF_GRAVITY_SMTP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'GF_GRAVITY_SMTP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'GRAVITY_MANAGER_URL' ) ) {
	define( 'GRAVITY_MANAGER_URL', 'https://gravityapi.com/wp-content/plugins/gravitymanager' );
}

if ( ! defined( 'GRAVITY_SUPPORT_URL' ) ) {
	define( 'GRAVITY_SUPPORT_URL', 'https://www.gravityforms.com/open-support-ticket/' );
}

require_once( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' );

add_action( 'plugins_loaded', array( \Gravity_Forms\Gravity_SMTP\Gravity_SMTP::class, 'pre_init' ), 0 );

add_action( 'init', array( \Gravity_Forms\Gravity_SMTP\Gravity_SMTP::class, 'load_plugin' ) );

add_action( 'plugins_loaded', array( \Gravity_Forms\Gravity_SMTP\Gravity_SMTP::class, 'run_upgrade_routines' ), -10 );

register_activation_hook( __FILE__, 'gravitysmtp_activation_hook' );

// Third Party Support
add_action( 'affwp_email_send_before', array( \Gravity_Forms\Gravity_SMTP\Gravity_SMTP::class, 'load_plugin' ) );
add_action( 'edd_email_header', array( \Gravity_Forms\Gravity_SMTP\Gravity_SMTP::class, 'load_plugin' ) );
add_action( 'groups_file_served', array( \Gravity_Forms\Gravity_SMTP\Gravity_SMTP::class, 'load_plugin' ), -10 );

function gravitysmtp_activation_hook() {
	\Gravity_Forms\Gravity_SMTP\Gravity_SMTP::activation_hook();
}
