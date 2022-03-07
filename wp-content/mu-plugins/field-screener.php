<?php

/**
 * Plugin Name: NYCO ACCESS NYC Field Screener
 * Description: Autoloads the backend of the Field Screener application.
 * Author: NYC Opportunity
 */

$dir = WPMU_PLUGIN_DIR . '/anyc-field-screener';

if (file_exists($dir)) {
  include_once $dir . '/Auth.php';
  include_once $dir . '/Util.php';
  require_once $dir . '/Backend.php';

  new FieldScreener\Backend();
}
