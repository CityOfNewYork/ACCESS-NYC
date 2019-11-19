<?php

/**
 * Plugin Name:  Send Me NYC
 * Description:  Email/SMS gateway for saving links for oneself
 * Author:       Blue State Digital
 * Text Domain:  smnyc
*/

require plugin_dir_path(__FILE__) . '/wp-send-me-nyc/SendMeNYC.php';

/**
 * Initialize plugin
 */

$contact = new SMNYC\ContactMe();
$sms = new SMNYC\SmsMe();
$email = new SMNYC\EmailMe('controllers/single-smnyc-email.php');

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
