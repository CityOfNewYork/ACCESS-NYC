<?php

/**
 * Plugin Name: Screening API Proxy
 * Description: Backend proxy for NYC Benefits Screening API eligibility requests
 * Author:      Blue State Digital
 */

namespace ScreeningApiProxy;

if (!defined('WPINC')) {
  die; //no direct access
}

require_once plugin_dir_path(__FILE__) . 'Class.php';

/**
 * Create Settings Page
 */

add_action('admin_menu', function() {
  add_options_page(
    'Screening API Proxy Settings',
    'Screening API Proxy',
    'manage_options',
    'screening_api_config',
    function() {
      echo '<div class="wrap">';
      echo '  <h1>Screening API Proxy Settings</h1>';
      echo '  <form method="post" action="options.php">';
      do_settings_sections('screening_api_config');
      settings_fields('screening_api_settings');
      submit_button();
      echo '  </form>';
      echo '</div>';
    }
  );
});

add_filter('plugin_action_links_' . plugin_basename(dirname(__FILE__) . '/ScreeningApiProxy.php'), function($links) {
  $settings_link = '<a href="'.esc_url(
    add_query_arg('page', 'screening_api_config', admin_url('options-general.php'))
  ).'">Settings</a>';

  array_unshift($links, $settings_link);

  return $links;
});

/**
 * Initialize plugin
 */

add_action('admin_init', function() {
  $screeningApiProxy = new ScreeningApiProxy();
  $screeningApiProxy = $screeningApiProxy->createSettingsSection();
});
