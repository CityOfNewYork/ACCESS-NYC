<?php

namespace S3PmxiCompatibility;

if (!defined('WPINC')) {
  die;
}

require_once plugin_dir_path(__FILE__) . 'Class.php';

$S3PmxiCompatibility = new S3PmxiCompatibility();

/**
 * This action fires before S3 Uploads initializes on the 'plugins_loaded'
 * action. It will check the 'page' query var for instances of the WP All
 * Import pages and deactivate the plugin if in view.
 */
add_action('muplugins_loaded', function() use ($S3PmxiCompatibility) {
  $page = (isset($_GET['page'])) ? $_GET['page'] : '';

  if ((is_admin() && in_array($page, $S3PmxiCompatibility->views))) {
    $S3PmxiCompatibility->deactivateS3();
  }
});

/**
 * This action is consistently the last WP All Import fires after every WP All
 * Import admin page is viewed. It will activate S3 Uploads in the event the
 * user navigates away from the plugin during import or closes the page.
 * Note: It is not currently documented.
 */
add_action('pmxi_action_after', [$S3PmxiCompatibility, 'activateS3']);

/**
 * This action fires before WP All Import starts the import process.
 * @link https://github.com/soflyy/wp-all-import-action-reference
 */
add_action('pmxi_before_xml_import', [$S3PmxiCompatibility, 'deactivateS3']);

/**
 * This action fires after WP All Import finishes the import process.
 * @link https://github.com/soflyy/wp-all-import-action-reference
 */
add_action('pmxi_after_xml_import', [$S3PmxiCompatibility, 'activateS3']);
