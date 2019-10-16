<?php
/*
  Plugin Name: StatCollector
  Description: Collects information from Drools and saved results
  Author:      Blue State Digital
*/

namespace StatCollector;

if (!defined('WPINC')) {
  die; //no direct access
}

require_once plugin_dir_path(__FILE__) . 'StatCollectorFunctions.php';
require_once plugin_dir_path(__FILE__) . 'SettingsFunctions.php';
require_once plugin_dir_path(__FILE__) . 'MockDatabase.php';

// Internal actions to call in a plugin/hook sense
add_action('drools_request', '\StatCollector\drools_request', 10, 2);
add_action('drools_response', '\StatCollector\drools_response', 10, 2);
add_action('results_sent', '\StatCollector\results_sent', 10, 5);
add_action('peu_data', '\StatCollector\peu_data', 10, 3);

// AJAX endpoints to directly write info
add_action('wp_ajax_response_update', '\StatCollector\response_update');
add_action('wp_ajax_nopriv_response_update', '\StatCollector\response_update');

// Create Settings Screens
add_action('admin_init', '\StatCollector\create_settings_section');
add_action('admin_menu', '\StatCollector\add_settings_page');

$path = plugin_basename(dirname(__FILE__) . '/StatCollector.php');
add_filter('plugin_action_links_' . $path, '\StatCollector\settings_link');

do_action('init_stat_collector');
