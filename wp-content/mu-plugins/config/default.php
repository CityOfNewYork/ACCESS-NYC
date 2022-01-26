<?php

/**
 * All environments config
 */

/**
 * Autoload Composer Dependencies
 */

require_once ABSPATH . '/vendor/autoload.php';

/**
 * Patch for missing WP Security Questions constant
 */

// phpcs:disable
define('wsq_CLASSES', '');
// phpcs:enable

/**
 * Have Bitly's WordPress Plugin use the token defined by constant.
 *
 * @author NYC Opportunity
 */
add_action('plugins_loaded', function() {
  if (is_plugin_active('wp-bitly/wp-bitly.php') && defined('SMNYC_WPBITLY_OAUTH_TOKEN')) {
    $opts = get_option(WPBITLY_OPTIONS);
    $opts['oauth_token'] = SMNYC_WPBITLY_OAUTH_TOKEN;

    update_option(WPBITLY_OPTIONS, $opts); // Use token from config
    update_option(WPBITLY_AUTHORIZED, true); // Set the authorization to true
  }
});

/**
 * Have GatherContent Importer Plugin use tokens defined by constants.
 *
 * @author NYC Opportunity
 */
add_action('plugins_loaded', function() {
  if (is_plugin_active('gathercontent-import/gathercontent-importer.php') &&
    defined('GATHERCONTENT_ACCOUNT_EMAIL') &&
    defined('GATHERCONTENT_PLATFORM_URL_SLUG') &&
    defined('GATHERCONTENT_API_KEY')) {
    $opts = get_option('gathercontent_importer');

    $opts['account_email'] = GATHERCONTENT_ACCOUNT_EMAIL;
    $opts['platform_url_slug'] = GATHERCONTENT_PLATFORM_URL_SLUG;
    $opts['api_key'] = GATHERCONTENT_API_KEY;

    update_option('gathercontent_importer', $opts); // Use tokens from config
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
