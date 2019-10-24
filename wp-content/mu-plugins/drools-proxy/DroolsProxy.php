<?php

/**
 * Plugin Name: Drools Proxy
 * Description: Backend Proxy for Drools web requests
 * Author:      Blue State Digital
 */

namespace DroolsProxy;

if (!defined('WPINC')) {
  die; //no direct access
}

require_once plugin_dir_path(__FILE__) . 'Class.php';

/**
 * Create Settings Page
 */

add_action('admin_menu', function() {
  add_options_page(
    'Drools Proxy Settings',
    'Drools Proxy',
    'manage_options',
    'drools_config',
    function() {
      echo '<div class="wrap">';
      echo '  <h1>Drools Proxy Settings</h1>';
      echo '  <form method="post" action="options.php">';
      do_settings_sections('drools_config');
      settings_fields('drools_settings');
      submit_button();
      echo '  </form>';
      echo '</div>';
    }
  );
});

add_filter('plugin_action_links_' . plugin_basename(dirname(__FILE__) . '/DroolsProxy.php'), function() {
  $settings_link = '<a href="'.esc_url(
    add_query_arg('page', 'drools_config', admin_url('options-general.php'))
  ).'">Settings</a>';

  array_unshift($links, $settings_link);

  return $links;
});

/**
 * Initialize plugin
 */

add_action('admin_init', function() {
  $droolsProxy = new DroolsProxy();
  $droolsProxy = $droolsProxy->createSettingsSection();
});
