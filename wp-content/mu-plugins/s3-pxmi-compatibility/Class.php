<?php

namespace S3PmxiCompatibility;

class S3PmxiCompatibility {
  /** WP All Import Views */
  public $views = [
    'pmxi-admin-import',
    'pmxi-admin-manage',
    'pmxi-admin-settings',
    'pmxi-admin-history'
  ];

  /** S3 Uploads Plugin path */
  public $s3 = 's3-uploads/s3-uploads.php';

  /**
   * Deactivate the S3 Uploads Plugin (if active).
   */
  public function deactivateS3() {
    if (is_plugin_active($this->s3)) {
      deactivate_plugins($this->s3);
    }
  }

  /**
   * Activates the S3 Uploads plugin if it exists and the constants are
   * defined in the WP Configuration.
   * @return  [WP_Error|null]  WP_Error on invalid file or 'NULL' on success.
   */
  public function activateS3() {
    if (is_admin() &&
      file_exists(WP_PLUGIN_DIR . '/' . $this->s3) &&
      defined('S3_UPLOADS_BUCKET') &&
      defined('S3_UPLOADS_KEY') &&
      defined('S3_UPLOADS_SECRET') &&
      defined('S3_UPLOADS_REGION')) {
      return activate_plugin($this->s3);
    }
  }
}
