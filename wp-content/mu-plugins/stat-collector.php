<?php

/**
 * Plugin Name: Stat Collector
 * Description: Collects data from Drools requests and saves it to a database.
 * Author: Blue State Digital
 */

if (file_exists(plugin_dir_path(__FILE__) . '/stat-collector/StatCollector.php')) {
  require plugin_dir_path(__FILE__) . '/stat-collector/StatCollector.php';
}
