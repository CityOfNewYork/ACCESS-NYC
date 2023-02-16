<?php

/**
 * Plugin Name:  NYCO Send Me NYC for WordPress
 * Description:  A developer plugin for WordPress that enables sharing website links via SMS or Email.
 * Author:       Blue State Digital, maintained by NYC Opportunity
 * Text Domain:  smnyc
*/

require plugin_dir_path(__FILE__) . '/wp-send-me-nyc/SendMeNYC.php';

/**
 * Initialize plugin
 */

$sms = new SMNYC\SmsMe();
$email = new SMNYC\EmailMe('controllers/smnyc-email.php');

/**
 * Register post types for the email and SMS templates.
 */
add_action('init', function() use ($email, $sms) {
  $email->registerPostType()->createEndpoints();
  $sms->registerPostType()->createEndpoints();
});

/**
 * SmsMe and EmailMe extend ContactMe. Each have settings that inherit some
 * settings from ContactMe. ContactMe was created for any generic email client
 * but EmailMe extends it for use with Amazon SES and adds additional settings
 * for. SmsMe creates the configuration and api for Twilio.
 */
add_action('admin_init', function() use ($sms, $email) {
  $sms->createSettingsSection();
  $email->createSettingsSection();
});
