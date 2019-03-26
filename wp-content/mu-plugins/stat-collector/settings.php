<?php
namespace StatCollector;

if (!defined('WPINC')) {
  die; //no direct access
}
add_action('admin_init', '\StatCollector\create_settings_section');
add_action('admin_menu', '\StatCollector\add_settings_page');
add_filter('plugin_action_links_'.plugin_basename(dirname(__FILE__).'/StatCollector.php'), '\StatCollector\settings_link');

function settings_link($links) {
  $settings_link = '<a href="'.esc_url(add_query_arg('page', 'collector_config', admin_url('options-general.php'))).'">Settings</a>';
  array_unshift($links, $settings_link);
  return $links;
}

function add_settings_page() {
  add_options_page(
    'Stat Collector Settings',
    'Stat Collector',
    'manage_options',
    'collector_config',
    '\StatCollector\settings_content'
  );
}

function settings_content() {
  echo '<div class="wrap">';
  echo '  <h1>Stat Collector Settings</h1>';
  echo '  <form method="post" action="options.php">';

  do_settings_sections('collector_config');
  settings_fields('statcollect_settings');
  submit_button();

  echo '  </form>';
  echo '</div>';
}

function create_settings_section() {
  add_settings_section('statcollect_aws', 'StatCollector Settings', '\StatCollector\settings_heading_text', 'collector_config');

  add_settings_field(
    'statc_host', //field name
    'MySQL host/endpoint', //label
    '\StatCollector\settings_field_html', // HTML content
    'collector_config', // page
    'statcollect_aws', // section
    ['statc_host', '']
  );

  add_settings_field(
    'statc_database',
    'Database Name',
    '\StatCollector\settings_field_html',
    'collector_config',
    'statcollect_aws',
    ['statc_database', '']
  );

  add_settings_field(
    'statc_user',
    'Database Username',
    '\StatCollector\settings_field_html',
    'collector_config',
    'statcollect_aws',
    ['statc_user', '']
  );

  add_settings_field(
    'statc_password',
    'Database Password',
    '\StatCollector\settings_field_html',
    'collector_config',
    'statcollect_aws',
    ['statc_password', '']
  );

  register_setting('statcollect_settings', 'statc_host');
  register_setting('statcollect_settings', 'statc_database');
  register_setting('statcollect_settings', 'statc_user');
  register_setting('statcollect_settings', 'statc_password');
}

function settings_heading_text(){
  echo "<p>Enter your MySQL credentials here.</p>";
}

function settings_field_html($args){
  echo "<input type='text' name='".$args[0]."' size=40 id='".$args[0]."' value='".get_option($args[0], '')."' placeholder='".$args[1]."' />";
  if ($_ENV[strtoupper($args[0])]) {
    echo '<p class="description">Environment currently set to <code>'.$_ENV[strtoupper($args[0])].'</code><p>';
  }
}
