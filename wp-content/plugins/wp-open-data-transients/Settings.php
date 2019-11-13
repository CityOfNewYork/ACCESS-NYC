<?php

namespace NYCO\Transients\Settings;

if (!is_admin()) {
  return;
}

/**
 * Dependencies
 */

use NYCO\Transients\Validations as Validations;

/**
 * Constants
 */

const TITLE = 'Open Data Transients';
const ID = 'open_data_transients';
const CAPABILITY = 'manage_options';

/**
 * Variables
 */

/** @var array The setting pages. */
$pages = array(
  [
    'page_title' => TITLE,
    'menu_title' => TITLE,
    'capability' => CAPABILITY,
    'menu_slug' => ID,
    'extra_action' => function () {
      settings_template(array(
        'id' => 'open_data_transients',
        'option' => get_option('open_data_transients_saved', null)
      ));
    }
  ]
);

/** @var array The Settings sections. */
$sections = array(
  [
    'id' => 'gtfs_feeds',
    'title' => 'Settings',
    'callback' => function () {
    },
    'page' => ID
  ]
);

/** @var array The settings fields. */
$settings = array(
  [
    'id' => 'open_data_app_token',
    'title' => 'App Token',
    'callback' => 'NYCO\Transients\Settings\settings_template',
    'page' => ID,
    'section' => $sections[0]['id'],
    'args' => [
      'id' => 'open_data_app_token',
      'placeholder' => ''
    ],
    'type' => 'string',
    'description' => '',
    'sanitize_callback' => 'NYCO\Transients\Validations\sanitize',
    'show_in_rest' => false,
    'default' => ''
  ],
  [
    'id' => 'open_data_transients_saved',
    'title' => 'Transients',
    'callback' => 'NYCO\Transients\Settings\settings_template',
    'page' => ID,
    'section' => '',
    'args' => [
      'id' => 'open_data_transients_saved',
      'placeholder' => ''
    ],
    'type' => 'string',
    'description' => '',
    'sanitize_callback' => 'NYCO\Transients\Validations\sanitize_transients',
    'show_in_rest' => false,
    'default' => null
  ],
  // Expiration
);


/**
 * Functions
 */

/**
 * Import templates based on argument callback parameters
 * @param  [array] $args Arguments supplied via WP callback
 */
function settings_template($args) {
  $template = __DIR__ . '/views/' . $args['id'] . '.php';
  if (file_exists($template)) {
    require $template;
  }
}

/**
 * Triggered before any other hook when a user accesses the admin area.
 * Creates the plugin settings sections then the plugin settings fields
 * and registers them.
 * @param  [array] $sections An array of sections to create.
 * @param  [array] $settings An array of settings to create.
 */
add_action('admin_init', function () use ($sections, $settings) {
  foreach ($sections as $section) {
    add_settings_section(
      $section['id'], $section['title'], $section['callback'], $section['page']
    );
  }

  foreach ($settings as $setting) {
    add_settings_field(
      $setting['id'], $setting['title'], $setting['callback'], $setting['page'],
      $setting['section'], $setting['args']
    );

    register_setting(ID, $setting['id'], array(
      'type' => $setting['type'],
      'description' => $setting['description'],
      'sanitize_callback' => $setting['sanitize_callback'],
      'show_in_rest' => $setting['show_in_rest'],
      'default' => $setting['default']
    ));
  }
});

/**
 * This action is used to add extra submenus and menu options to the admin
 * panel's menu structure. It runs after the basic admin panel menu structure
 * is in place. Adds the plugin menu page and the page content.
 * @param  [array] $pages An array of pages to create.
 */
add_action('admin_menu', function () use ($pages) {
  foreach ($pages as $page) {
    add_options_page(
      $page['page_title'], $page['menu_title'], $page['capability'],
      $page['menu_slug'],
      function () use ($page) {
        echo '<div class="wrap">';
        echo '  <h1>' . TITLE . '</h1>';
        echo '  <form method="post" action="options.php">';

        do_settings_sections(ID);
        settings_fields(ID);
        submit_button();

        echo '  </form>';

        if (isset($page['extra_action'])) {
          $page['extra_action']();
        }

        echo '</div>';
      }
    );
  }
});
