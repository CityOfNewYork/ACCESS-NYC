<?php

// phpcs:disable
/**
 * Plugin Name: NYCO WordPress Gather Content Templates Sync
 * Description: A developer plugin for WordPress that saves template mappings created by the Gather Content WordPress integration to local JSON files for import in downstream environments.
 * Author: NYC Opportunity
 */
// phpcs:enable

if (file_exists(WPMU_PLUGIN_DIR . '/wp-gc-templates-sync/GcTemplatesSync.php')) {
  require_once WPMU_PLUGIN_DIR . '/wp-gc-templates-sync/GcTemplatesSync.php';

  new NYCO\GcTemplatesSync();
}
