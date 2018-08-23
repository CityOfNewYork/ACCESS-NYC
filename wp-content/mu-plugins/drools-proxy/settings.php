<?php
namespace Drools;

if (!defined('WPINC')) {
  die; //no direct access
}
add_action('admin_init', '\Drools\create_settings_section');
add_action('admin_menu', '\Drools\add_settings_page');
add_filter('plugin_action_links_'.plugin_basename(dirname(__FILE__).'/DroolsProxy.php'), '\Drools\settings_link');

function settings_link($links) {
  $settings_link = '<a href="'.esc_url(
    add_query_arg('page', 'drools_config', admin_url('options-general.php'))
  ).'">Settings</a>';

  array_unshift($links, $settings_link);

  return $links;
}

function add_settings_page() {
  add_options_page(
    'Drools Proxy Settings',
    'DroolsProxy',
    'manage_options',
    'drools_config',
    '\Drools\settings_content'
  );
}

function settings_content() {
  echo '<div class="wrap">';
  echo '  <h1>Drools Proxy Settings</h1>';
  echo '  <form method="post" action="options.php">';

  do_settings_sections('drools_config');
  settings_fields('drools_settings');
  submit_button();

  echo '  </form>';
  echo '</div>';
}

function create_settings_section() {
  add_settings_section('drools_proxy', 'Drools Settings', '\Drools\settings_heading_text', 'drools_config');

  add_settings_field(
    'drools_url', //field name
    'Drools Endpoint (URL)', //label
    '\Drools\settings_field_html', // HTML content
    'drools_config', // page
    'drools_proxy', // section
    ['drools_url', '']
  );

  add_settings_field(
    'drools_user',
    'User',
    '\Drools\settings_field_html',
    'drools_config',
    'drools_proxy',
    ['drools_user', '']
  );

  add_settings_field(
    'drools_pass',
    'Password',
    '\Drools\settings_field_html',
    'drools_config',
    'drools_proxy',
    ['drools_pass', '']
  );

  register_setting('drools_settings', 'drools_url');
  register_setting('drools_settings', 'drools_user');
  register_setting('drools_settings', 'drools_pass');
}

function settings_heading_text(){
  echo '<p>Enter your Drools credentials here.</p>';
}

function settings_field_html($args){
  echo "<input type='text' name='".$args[0]."' size=40 id='".$args[0]."' value='".get_option($args[0], '')."' placeholder='".$args[1]."' />";
  if ($_ENV[strtoupper($args[0])]) {
    echo '<p class="description">Environment currently set to "'.$_ENV[strtoupper($args[0])].'"<p>';
  }
}
