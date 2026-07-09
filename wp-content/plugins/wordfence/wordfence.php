<?php
/*
Plugin Name: Wordfence Security
Plugin URI: https://www.wordfence.com/
Description: Wordfence Security - Anti-virus, Firewall and Malware Scan
Author: Wordfence
Version: 8.2.2
Author URI: https://www.wordfence.com/
Text Domain: wordfence
Domain Path: /languages
Network: true
Requires at least: 4.7
Requires PHP: 7.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

@copyright Copyright (C) 2012-2026 Defiant Inc.
@license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.

*/
if(defined('WP_INSTALLING') && WP_INSTALLING){
	return;
}

if (!defined('ABSPATH')) {
	exit;
}
define('WORDFENCE_VERSION', '8.2.2');
define('WORDFENCE_BUILD_NUMBER', '1778685035');
define('WORDFENCE_BASENAME', function_exists('plugin_basename') ? plugin_basename(__FILE__) :
	basename(dirname(__FILE__)) . '/' . basename(__FILE__));

global $wp_plugin_paths;
foreach ($wp_plugin_paths as $dir => $realdir) {
	if (strpos(__FILE__, $realdir) === 0) {
		define('WORDFENCE_FCPATH', $dir . '/' . basename(__FILE__));
		define('WORDFENCE_PATH', trailingslashit($dir));
		break;
	}
}
if (!defined('WORDFENCE_FCPATH')) {
	/** @noinspection PhpConstantReassignmentInspection */
	define('WORDFENCE_FCPATH', __FILE__);
	/** @noinspection PhpConstantReassignmentInspection */
	define('WORDFENCE_PATH', trailingslashit(dirname(WORDFENCE_FCPATH)));
}
if (!defined('WF_IS_WP_ENGINE')) {
	define('WF_IS_WP_ENGINE', isset($_SERVER['IS_WPE']));
}
if (!defined('WF_IS_FLYWHEEL')) {
	define('WF_IS_FLYWHEEL', isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Flywheel/') === 0);
}
if (!defined('WF_IS_PRESSABLE')) {
	define('WF_IS_PRESSABLE', (defined('IS_ATOMIC') && IS_ATOMIC) || (defined('IS_PRESSABLE') && IS_PRESSABLE));
}

require(dirname(__FILE__) . '/lib/wfVersionSupport.php');
/**
 * @var string $wfPHPDeprecatingVersion
 * @var string $wfPHPMinimumVersion
 * @var string $wfWordPressDeprecatingVersion
 * @var string $wfWordPressMinimumVersion
 */

if (!defined('WF_PHP_UNSUPPORTED')) {
	define('WF_PHP_UNSUPPORTED', version_compare(PHP_VERSION, $wfPHPMinimumVersion, '<'));
}

if (WF_PHP_UNSUPPORTED) {
	add_action('all_admin_notices', 'wfUnsupportedPHPOverlay');

	function wfUnsupportedPHPOverlay() {
		include "views/unsupported-php/admin-message.php";
	}
	return;
}

if (!defined('WF_WP_UNSUPPORTED')) {
	require(ABSPATH . 'wp-includes/version.php'); /** @var string $wp_version */
	define('WF_WP_UNSUPPORTED', version_compare($wp_version, $wfWordPressMinimumVersion, '<'));
}

if (WF_WP_UNSUPPORTED) {
	add_action('all_admin_notices', 'wfUnsupportedWPOverlay');
	
	function wfUnsupportedWPOverlay() {
		include "views/unsupported-wp/admin-message.php";
	}
	return;
}

if(get_option('wordfenceActivated') != 1){
	add_action('activated_plugin','wordfence_save_activation_error'); function wordfence_save_activation_error(){ update_option('wf_plugin_act_error',  ob_get_contents()); }
}
if(! defined('WORDFENCE_VERSIONONLY_MODE')){ //Used to get version from file.
	//Duplicate block of wfUtils::memoryLimit(), copied here to avoid needing to include the class at this point of execution
	$maxMemory = ini_get('memory_limit');
	if (!(is_string($maxMemory) || is_numeric($maxMemory)) || !preg_match('/^\s*\d+[GMK]?\s*$/i', $maxMemory)) { $maxMemory = '128M'; } //Invalid or unreadable value, default to our minimum
	$last = strtolower(substr($maxMemory, -1));
	$maxMemory = (int) $maxMemory;
	
	if ($last == 'g') { $maxMemory = $maxMemory * 1024 * 1024 * 1024; }
	else if ($last == 'm') { $maxMemory = $maxMemory * 1024 * 1024; }
	else if ($last == 'k') { $maxMemory = $maxMemory * 1024; }
	
	if ($maxMemory < 134217728 /* 128 MB */ && $maxMemory > 0 /* Unlimited */) {
		$disabled = ini_get('disable_functions');
		if (!is_string($disabled) || strpos(ini_get('disable_functions'), 'ini_set') === false) {
			@ini_set('memory_limit', '128M'); //Some hosts have ini set at as little as 32 megs. 128 is the min sane amount of memory.
		}
	}

	/**
	 * Constant to determine if Wordfence is installed on another WordPress site one or more directories up in
	 * auto_prepend_file mode.
	 */
	define('WFWAF_SUBDIRECTORY_INSTALL', class_exists('wfWAF') &&
		!in_array(realpath(dirname(__FILE__) . '/vendor/wordfence/wf-waf/src/init.php'), get_included_files()));
	if (!WFWAF_SUBDIRECTORY_INSTALL) {
		require_once(dirname(__FILE__) . '/vendor/wordfence/wf-waf/src/init.php');
		if (!wfWAF::getInstance()) {
			define('WFWAF_AUTO_PREPEND', false);
			require_once(dirname(__FILE__) . '/waf/bootstrap.php');
		}
	}
	
	//Modules

	//Load
	require_once(dirname(__FILE__) . '/lib/wordfenceConstants.php');
	require_once(dirname(__FILE__) . '/lib/wfI18n.php');
	require_once(dirname(__FILE__) . '/lib/wordfenceClass.php');
	wordfence::install_actions();
}
