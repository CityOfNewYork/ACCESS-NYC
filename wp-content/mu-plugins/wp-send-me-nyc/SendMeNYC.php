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

/**
 * Add settings link to options page in plugin menu
 *
 * @author NYC Opportunity
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function() {
  $href = esc_url(add_query_arg('page', 'smnyc_config', admin_url('options-general.php')));
  $settings_link = '<a href="' . $href . '">Settings</a>';

  array_unshift($links, $settings_link);

  return $links;
});

/**
 * Have Bitly's WordPress Plugin use the token defined by constant.
 *
 * @author NYC Opportunity
 */
add_action('plugins_loaded', function() {
  if (is_plugin_active('wp-bitly/wp-bitly.php') && defined('WPBITLY_OPTIONS') && defined('SMNYC_WPBITLY_OAUTH_TOKEN')) {
    $opts = get_option(WPBITLY_OPTIONS);
    $opts['oauth_token'] = SMNYC_WPBITLY_OAUTH_TOKEN;

    update_option(WPBITLY_OPTIONS, $opts); // Use token from config
    update_option(WPBITLY_AUTHORIZED, true); // Set the authorization to true
  }
});

/**
 * Remove the OAuth button field from the WP Bit.ly Admin settings
 * (Settings > Writing)
 *
 * @author NYC Opportunity
 */
add_action('admin_init', function() {
  if (is_plugin_active('wp-bitly/wp-bitly.php') && defined('SMNYC_WPBITLY_OAUTH_TOKEN')) {
    global $wp_settings_fields;

    unset($wp_settings_fields['writing']['wpbitly_settings']['authorize']);
  }
}, 11);

/**
 * Add an admin notice to activate Bitly's WordPress Plugin
 *
 * @author NYC Opportunity
 */
add_action('admin_notices', function() {
  if (false === is_plugin_active('wp-bitly/wp-bitly.php')) {
    echo '<div class="notice is-dismissible">';
    echo '  <p>' . __('To enable the <b>NYCO Send Me NYC for WordPress</b> plugin
      to create custom short links, please activate <b>Bitly\'s Wordpress Plugin</b>
      in the <a href="' . admin_url('plugins.php') . '">plugins menu</a> and configure
      the plugin settings.') . '</p>';
    echo '</div>';
  }
});