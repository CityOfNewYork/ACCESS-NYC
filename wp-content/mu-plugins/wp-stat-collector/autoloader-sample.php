<?php

/**
 * Plugin Name: Stat Collector
 * Description: Adds WordPress hooks to enable the logging of data from the site to a specified MySQL database.
 * Author:      Blue State Digital, maintained by NYC Opportunity
 */

/**
 *
 */

add_action('statc_register', function($statc) {
  /**
   * Hook to save data
   *
   * @param  String  $data  My Data String
   */
  add_action('my_action', function($data) use ($statc) {
    if (gettype($data) === 'string') {
      $statc->collect('my_table', [
        'my_data' => $data,
      ]);
    }
  }, $statc->settings->priority, 2);

  return true;
});

/**
 * Creates the database tables for data if they do not exist.
 *
 * @param  Object  $db  Instance of wpdb (WordPress DB abstraction method)
 */
add_action('statc_bootstrap', function($db) {
  $db->query(
    'CREATE TABLE IF NOT EXISTS my_table (
      id INT(11) NOT NULL AUTO_INCREMENT,
      my_data TEXT DEFAULT NULL,
      PRIMARY KEY(id)
    ) ENGINE=InnoDB'
  );

  return true;
});

/**
 * Include and initialize the plugin
 */

require plugin_dir_path(__FILE__) . '/stat-collector/StatCollector.php';
