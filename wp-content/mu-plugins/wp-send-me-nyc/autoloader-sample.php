<?php

/**
 * Plugin Name: NYCO Send Me NYC for WordPress
 * Description: A developer plugin for WordPress that enables sharing website links via SMS or Email.
 * Author:      Blue State Digital, maintained by NYC Opportunity
 * Text Domain: smnyc
 */

require plugin_dir_path(__FILE__) . '/wp-send-me-nyc/SendMeNYC.php';

/**
 * Initialize plugin
 *
 * @author NYC Opportunity
 */

$contact = new SMNYC\ContactMe();
$sms = new SMNYC\SmsMe();
$email = new SMNYC\EmailMe();

/**
 * New objects and their properties could be customized here. These
 * would be passed along similarly to the 'init' and 'admin_init' actions.
 *
 * @author NYC Opportunity
 */

// $newSms = new SMNYC\SmsMe();
// $newSms->action = 'a_new_action_name';
// $newSms->prefix = 'a_new_options_prefix';
// $newSms->post_type = 'a-new-post-type';

/**
 * Register post types for the email and SMS templates.
 *
 * @author NYC Opportunity
 */
add_action('init', function() use ($email, $sms) {
  $email->registerPostType()->createEndpoints();
  $sms->registerPostType()->createEndpoints();
});

/**
 * SmsMe and EmailMe extend ContactMe. Each have settings that inherit some
 * settings from ContactMe. ContactMe was created for any generic email client
 * but EmailMe extends it for use with Amazon SES and adds additional settings
 * for. Bitly settings can be found in the ContactMe class. SmsMe creates the
 * configuration and api for Twilio.
 *
 * @author NYC Opportunity
 */
add_action('admin_init', function() use ($contact, $sms, $email) {
  $contact->createBitlySection();
  $sms->createSettingsSection();
  $email->createSettingsSection();
});
