<?php

// phpcs:disable
/**
 * Plugin Name: S3 Uploads / WP All Import Compatibility
 * Description: Disables the S3 Uploads plugin while WP All Import is being viewed or used in the admin. This ensures the plugin can operate on the uploads directory normally.
 * Author: NYC Opportunity
 */
// phpcs:enable

require_once plugin_dir_path(__FILE__) . '/s3-pxmi-compatibility/S3PxmiCompatibility.php';
