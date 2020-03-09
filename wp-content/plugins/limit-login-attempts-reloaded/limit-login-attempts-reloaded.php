<?php
/*
Plugin Name: Limit Login Attempts Reloaded
Description: Limit the rate of login attempts, including by way of cookies and for each IP address.
Author: WPChef
Author URI: https://wpchef.org
Text Domain: limit-login-attempts-reloaded
Version: 2.10.0

Copyright 2008 - 2012 Johan Eenfeldt, 2016 - 2019 WPChef
*/

/***************************************************************************************
 * Constants
 **************************************************************************************/
define( 'LLA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LLA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/***************************************************************************************
 * Different ways to get remote address: direct & behind proxy
 **************************************************************************************/
define( 'LLA_DIRECT_ADDR', 'REMOTE_ADDR' );
define( 'LLA_PROXY_ADDR', 'HTTP_X_FORWARDED_FOR' );

/* Notify value checked against these in limit_login_sanitize_variables() */
define( 'LLA_LOCKOUT_NOTIFY_ALLOWED', 'log,email' );

$limit_login_my_error_shown = false; /* have we shown our stuff? */
$limit_login_just_lockedout = false; /* started this pageload??? */
$limit_login_nonempty_credentials = false; /* user and pwd nonempty */

/***************************************************************************************
 * Include files
 **************************************************************************************/
require_once( LLA_PLUGIN_DIR . '/core/Helpers.php' );
require_once( LLA_PLUGIN_DIR . '/core/Logger.php' );
require_once( LLA_PLUGIN_DIR . '/core/LimitLoginAttempts.php' );

$limit_login_attempts_obj = new Limit_Login_Attempts();