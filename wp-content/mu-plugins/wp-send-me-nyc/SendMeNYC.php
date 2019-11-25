<?php

namespace SMNYC;

if (!defined('WPINC')) {
  die; // no direct access
}

require_once plugin_dir_path(__FILE__) . 'ContactMe.php';
require_once plugin_dir_path(__FILE__) . 'SmsMe.php';
require_once plugin_dir_path(__FILE__) . 'EmailMe.php';
require_once plugin_dir_path(__FILE__) . 'Functions.php';

/**
 * Create Settings Page
 */

add_action('admin_menu', function() {
  add_options_page(
    'Send Me NYC Settings',
    'Send Me NYC',
    'manage_options',
    'smnyc_config',
    function() {
      echo '<div class="wrap">';
      echo '  <h1>Send Me NYC Settings</h1>';
      echo '  <form method="post" action="options.php">';
      do_settings_sections('smnyc_config');
      settings_fields('smnyc_settings');
      submit_button();
      echo '  </form>';
      echo '</div>';
    }
  );
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function() {
  $href = esc_url(add_query_arg('page', 'smnyc_config', admin_url('options-general.php')));
  $settings_link = '<a href="' . $href . '">Settings</a>';

  array_unshift($links, $settings_link);

  return $links;
});
