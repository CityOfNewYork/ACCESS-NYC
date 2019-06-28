<?php

/**
 * Plugin Name: StatCollector
 * Description: Collects information from Drools and saved results
 * Author:      Blue State Digital
 */

namespace StatCollector;

if (!defined('WPINC')) {
  die; //no direct access
}

require_once plugin_dir_path(__FILE__) . 'StatCollectorClass.php';
require_once plugin_dir_path(__FILE__) . 'MockDatabase.php';

/**
 * Create Settings Page
 */

add_action('admin_menu', function() {
  add_options_page(
    'Stat Collector Settings',
    'Stat Collector',
    'manage_options',
    'collector_config',
    function() {
      echo '<div class="wrap">';
      echo '  <h1>Stat Collector Settings</h1>';
      echo '  <form method="post" action="options.php">';
      do_settings_sections('collector_config');
      settings_fields('statcollect_settings');
      submit_button();
      echo '  </form>';
      echo '</div>';
    }
  );
});

add_filter('plugin_action_links_' . plugin_basename(dirname(__FILE__) . '/StatCollector.php'), function($links) {
  $admin = admin_url('options-general.php');
  $page = add_query_arg('page', 'collector_config', $admin);
  $settings_link = '<a href="' . esc_url($page) . '">Settings</a>';
  array_unshift($links, $settings_link);
  return $links;
});

/**
 * Initialize plugin
 */

$stat_collector = new StatCollector();
add_action('admin_init', function() use ($stat_collector) {
  $stat_collector->createSettingsSection();
});
