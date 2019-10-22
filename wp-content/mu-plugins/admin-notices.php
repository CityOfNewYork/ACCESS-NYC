<?php

/**
 * Plugin Name: Admin Notices
 * Description: Notifications for site admins.
 * Author: Blue State Digital
 */

add_action('admin_notices', function() {
  if (!class_exists('Timber')) {
    echo 'Timber not activated. Make sure you activate the plugin in ' .
      '<a href="/wp-admin/plugins.php#timber">/wp-admin/plugins.php</a>';
  }
});
