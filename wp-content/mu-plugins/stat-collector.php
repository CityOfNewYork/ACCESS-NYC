<?php

/**
 * Plugin Name: Stat Collector
 * Description: Collects data from Drools requests and saves it to a database.
 * Author: Blue State Digital
 */

if (file_exists(WPMU_PLUGIN_DIR . '/statcollector/StatCollector.php'))
  require WPMU_PLUGIN_DIR . '/statcollector/StatCollector.php';