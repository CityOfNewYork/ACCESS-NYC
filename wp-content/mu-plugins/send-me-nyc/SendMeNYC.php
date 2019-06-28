<?php

/**
 * Plugin Name:  Send Me NYC
 * Description:  Email/SMS gateway for saving links for onesself
 * Author:       Blue State Digital
 * Text Domain:  smnyc
 * Requirements: The plugin doesn't include dependencies. These should be added
 *               to the root Composer file for the site (composer require ...)
 *               twilio/sdk: ^5.32,
 *               aws/aws-sdk-php: ^3.99
*/

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

/**
 * Initialize plugin
 */

$contact = new ContactMe();
$sms = new SmsMe();
$email = new EmailMe();

/**
 * Register post types for the email and SMS templates.
 */
add_action('init', function() use ($email, $sms) {
  $email->registerPostType();
  $sms->registerPostType();
});

/**
 * SmsMe and EmailMe extend ContactMe. Each have settings that inherit some
 * settings from ContactMe. ContactMe was created for any generic email client
 * but EmailMe extends it for use with Amazon SES and adds additional settings
 * for. Bitly settings can be found in the ContactMe class. SmsMe creates the
 * configuration and api for Twilio.
 */
add_action('admin_init', function() use ($contact, $sms, $email) {
  $contact->createBitlySection();
  $sms->createSettingsSection();
  $email = $email->createSettingsSection();
});
