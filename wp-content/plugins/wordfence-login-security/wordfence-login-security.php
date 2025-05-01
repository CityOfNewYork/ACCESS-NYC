<?php
/*
Plugin Name: Wordfence Login Security
Description: Wordfence Login Security
Author: Wordfence
Author URI: https://www.wordfence.com/
Version: 1.1.15
Network: true
Requires at least: 4.7
Requires PHP: 7.0
Text Domain: wordfence-login-security
Domain Path: /languages
@copyright Copyright (C) 2019-2023 Defiant Inc.
*/
if (defined('WP_INSTALLING') && WP_INSTALLING) { return; }
if (!defined('ABSPATH')) { exit; }

$wfCoreActive = false;
$plugins = (array) get_option('active_plugins', array()); //Used in lieu of is_plugin_active since that's not loaded until admin_init
if (is_multisite()) {
	$sitePlugins = array_keys((array) get_site_option('active_sitewide_plugins', array()));
	$plugins = array_merge($plugins, $sitePlugins);
}

$wfVersion = ((is_multisite() && function_exists('get_network_option')) ? get_network_option(null, 'wordfence_version', false) : get_option('wordfence_version', false));
if (version_compare($wfVersion, '7.3.1', '>=')) {
	foreach ($plugins as $p) {
		if (preg_match('~^wordfence[^/]*/wordfence\.php$~i', $p)) {
			$wfCoreActive = true;
			break;
		}
	}
}

if ($wfCoreActive && !(isset($wfCoreLoading) && $wfCoreLoading)) {
	return; //Wordfence core will load this, prevent the standalone one from also loading if active
}
else {
	define('WORDFENCE_LS_FROM_CORE', ($wfCoreActive && isset($wfCoreLoading) && $wfCoreLoading));
	
	define('WORDFENCE_LS_VERSION', '1.1.15');
	define('WORDFENCE_LS_BUILD_NUMBER', '1736959993');

	define('WORDFENCE_LS_PLUGIN_BASENAME', plugin_basename(__FILE__));
	
	if (!defined('WORDFENCE_LS_EMAIL_VALIDITY_DURATION_MINUTES')) { define('WORDFENCE_LS_EMAIL_VALIDITY_DURATION_MINUTES', 15); }
	
	if (!WORDFENCE_LS_FROM_CORE) {
		global $wp_plugin_paths;
		foreach ($wp_plugin_paths as $dir => $realdir) {
			if (strpos(__FILE__, $realdir) === 0) {
				define('WORDFENCE_LS_FCPATH', $dir . '/' . basename(__FILE__));
				define('WORDFENCE_LS_PATH', trailingslashit($dir));
				break;
			}
		}
	}
	
	if (!defined('WORDFENCE_LS_FCPATH')) {
		/** @noinspection PhpConstantReassignmentInspection */
		define('WORDFENCE_LS_FCPATH', __FILE__);
		/** @noinspection PhpConstantReassignmentInspection */
		define('WORDFENCE_LS_PATH', trailingslashit(dirname(WORDFENCE_LS_FCPATH)));
	}
	
	if (!function_exists('wordfence_ls_autoload')) {
		function wordfence_ls_autoload($class) {
			$class = str_replace('\\', '/', $class);
			$class = str_replace('\\\\', '/', $class);
			$components = explode('/', $class);
			if (count($components) < 2) {
				return false;
			}
			
			if ($components[0] != 'WordfenceLS') {
				return false;
			}
			
			$path = '';
			for ($i = 1; $i < count($components) - 1; $i++) {
				$path .= '/' . strtolower($components[$i]);
			}
			
			if (preg_match('/^(Controller|Model|Utility)_([a-z0-9]+)$/i', $components[count($components) - 1], $matches)) {
				$path = dirname(__FILE__) . '/classes/' . strtolower($matches[1]) . $path . '/' . strtolower($matches[2]) . '.php';
				if (file_exists($path)) {
					require_once($path);
					return true;
				}
			}
			
			return false;
		}
	}
	
	if (!defined('WORDFENCE_LS_AUTOLOADER_REGISTERED')) {
		define('WORDFENCE_LS_AUTOLOADER_REGISTERED', true);
		spl_autoload_register('wordfence_ls_autoload');
	}
	
	if (!defined('WORDFENCE_LS_VERSIONONLY_MODE') && (!defined('WORDFENCE_USE_LEGACY_2FA') || (defined('WORDFENCE_USE_LEGACY_2FA') && !WORDFENCE_USE_LEGACY_2FA))) { //Used to get version from file
		\WordfenceLS\Controller_WordfenceLS::shared()->init();
	}
}