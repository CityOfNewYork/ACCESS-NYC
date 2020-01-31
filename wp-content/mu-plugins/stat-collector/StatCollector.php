<?php

namespace StatCollector;

if (!defined('WPINC')) {
  die; //no direct access
}

require_once ABSPATH . WPINC . '/wp-db.php'; // wpdb; WordPress DB abstraction
require_once plugin_dir_path(__FILE__) . 'wp-db-ssl.php'; // wpdb SSL extention
require_once plugin_dir_path(__FILE__) . 'Class.php';
require_once plugin_dir_path(__FILE__) . 'Settings.php';
require_once plugin_dir_path(__FILE__) . 'Check.php';
require_once plugin_dir_path(__FILE__) . 'MockDatabase.php';

/**
 * Init Stat Collector, and options page.
 */

$settings = new Settings();

/**
 * Init Stat Collector
 */
add_action('init', function() use ($settings) {
  new StatCollector($settings);
});

/**
 * Init Admin Options
 */
add_action('admin_menu', function() use ($settings) {
  $check = new Check($settings);

  $check->certificateAuthority();
  $check->connection();
  $check->tables();

  $settings->addOptions()->addSettings();
}, $settings->priority);
