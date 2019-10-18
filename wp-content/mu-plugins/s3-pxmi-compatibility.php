<?php

/**
 * Plugin Name: S3 Uploads / WP All Import Compatibility
 * Description: Disables the S3 Uploads plugin while WP All Import is being viewed or used in the admin. This ensures the plugin can operate on the uploads directory normally.
 * Author: NYC Opportunity
 */

$S3PxmiCompatibility = '/s3-pxmi-compatibility/S3PxmiCompatibility.php';

if (file_exists(plugin_dir_path(__FILE__) . $S3PxmiCompatibility)) {
  require_once plugin_dir_path(__FILE__) . $S3PxmiCompatibility;
}
