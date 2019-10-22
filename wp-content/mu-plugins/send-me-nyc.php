<?php

/**
 * Plugin Name: Send Me NYC
 * Description: Email/SMS gateway for saving links for oneself.
 * Author: Blue State Digital
 */

if (file_exists(plugin_dir_path(__FILE__) . '/send-me-nyc/SendMeNYC.php')) {
  require plugin_dir_path(__FILE__) . '/send-me-nyc/SendMeNYC.php';
}
