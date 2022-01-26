<?php

// phpcs:disable
/**
 * Plugin Name: NYCO WordPress Assets Integrations
 * Description: Add an Advanced Custom Fields option page for toggling NYCO WordPress Assets integrations. This plugin used the integrations.yml file to create toggles for each integration. If the integration is disabled, the NYCO WordPress Assets plugin will not register and enqueue it.
 * Author: NYC Opportunity
 * Author URI: nyc.gov/opportunity
 */
// phpcs:enable

use NYCO\WpAssets as WpAssets;

add_action('acf/init', function() {
  if (!isset($GLOBALS['wp_assets'])) {
    $GLOBALS['wp_assets'] = new WpAssets();
  }

  if (!isset($GLOBALS['wp_integrations'])) {
    $GLOBALS['wp_integrations'] = $GLOBALS['wp_assets']->loadIntegrations();
  }

  // Check function exists.
  if (function_exists('acf_add_options_page') && isset($GLOBALS['wp_integrations'])) {
    $optionsPageTitle = __('NYCO WordPress Assets Integrations');

    $optionsPageKey = 'nyco-wp-assets-options-integrations';

    $optionsFieldGroupKey = 'nyco_wp-assets-integration-fields';

    $optionsInstructions = array(
      'key' => 'field_integrations-readme',
      'label' => __('Readme'),
      'type' => 'message',
      // phpcs:disable
      'message' => __('Integrations are managed via the ' .
        '<a href="https://github.com/CityOfNewYork/nyco-wp-assets" target="_blank">NYCO WP Assets plugin</a> ' .
        'and a local YAML config file <code>wp-content/mu-plugins/config/integrations.yml</code>. Integrations ' .
        'added through the configuration file will appear here and can be toggled on or off. Keep in mind some ' .
        'integrations are required for others to function properly. The integrations file is ' .
        '<a href="https://github.com/CityOfNewYork/working-nyc/blob/main/wp-content/mu-plugins/config/integrations.yml" target="_blank">checked into version control</a> ' .
        'for safe keeping and deployed with the site. Integrations are managed this way to ensure the following; ' .
        'centralized management of external dependencies, optimized for performance, functioning properly across ' .
        'all environments, and secure storage of integration keys and secrets. Container keys, tags, and secrets ' .
        'are managed via the ' .
        '<a href="https://github.com/CityOfNewYork/nyco-wp-config" target="_blank">NYCO WP Config plugin</a> ' .
        'and a local YAML config file <code>wp-content/mu-plugins/config/config.yml</code> which ensures the ' .
        'correct keys are used for each environment when the site is deployed to different environments ' .
        'downstream. This file is not checked into the repository for security reasons. It can be edited ' .
        'manually using SSH or locally using the ' .
        '<a href="https://github.com/CityOfNewYork/nyco-wp-boilerplate#config" target="_blank">NYCO WP Boilerplate</a> ' .
        'and uploaded using the <code>bp config</code> command.'),
      // phpcs:enable
      'new_lines' => 'wpautop',
      'esc_html' => 0
    );

    $parent = acf_add_options_page(array(
      'page_title' => __('Integrations'),
      'menu_title' => __('Integrations'),
      'menu_slug' => $optionsPageKey,
      'capability' => 'activate_plugins',
      'redirect' => false
    ));

    // Convert data from the integrations.yml file into ACF fields.
    $fields = array_map(function($i) {
      return array(
        'key' => 'field_' . $i['handle'],
        'label' => $i['name'],
        'name' => $i['handle'],
        'type' => 'radio',
        'instructions' => $i['instructions'],
        'required' => 0,
        'choices' => array(
          'true' => __('True'),
          'false' => __('False')
        ),
        'allow_null' => 0,
        'other_choice' => 0,
        'default_value' => __('True'),
        'return_format' => 'value',
      );
    }, $GLOBALS['wp_integrations']);

    // Add the instructions field to the page.
    array_unshift($fields, $optionsInstructions);

    acf_add_local_field_group(array(
      'key' => $optionsFieldGroupKey,
      'title' => $optionsPageTitle,
      'fields' => $fields,
      'location' => [
        [
          array(
            'param' => 'options_page',
            'operator' => '==',
            'value' => $optionsPageKey
          ),
        ],
      ],
    ));
  }
});
