<?php

// phpcs:disable
/**
 * Plugin Name: S3 Uploads / WP All Import Compatibility
 * Description: A developer plugin for WordPress that ensures compatibility between hummanmade/S3 Uploads and WP All Import. It disables S3 Uploads while WP All Import is being viewed or used in the admin. This ensures the plugin can operate on the uploads directory normally.
 * Author: NYC Opportunity
 */
// phpcs:enable

require_once plugin_dir_path(__FILE__) . '/wp-s3-all-import-compatibility/S3PxmiCompatibility.php';
