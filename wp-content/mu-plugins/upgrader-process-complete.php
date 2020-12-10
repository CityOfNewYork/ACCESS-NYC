<?php

/**
 * Update composer.json with new plugin version number metadata. If the
 * plugin is required by composer. Fires when the update process is complete.
 *
 * When comparing the updated plugin with the package it strips the vendor
 * prefix from the package name checking the directory name of the plugin.
 * There could be cases where a composer php package could be confused with
 * a plugin.
 *
 * WordPress doesn't support special characters in the plugin's defined version
 * such as carets or tildés so every package uses a literal semantic version.
 *
 * @source  https://developer.wordpress.org/reference/hooks/upgrader_process_complete/
 *
 * @param   Instance  $WP_Upgrader  WP_Upgrader instance
 * @param   Object    $hook_extra   Array of bulk item update data.
 */
add_action('upgrader_process_complete', function($WP_Upgrader, $hook_extra) {
  /**
   * Only trigger if this is a plugin update
   */

  if ('plugin' !== $hook_extra['type'] || 'update' !== $hook_extra['action']) {
    return;
  }

  /**
   * Read the Composer package at the root of the WordPress site directory
   *
   * @return  Object  Key value array representation of the composer.json file
   */
  $get_composer = function() {
    return json_decode(file_get_contents(ABSPATH . '/composer.json'), true);
  };

  /**
   * Write a key value array representation to the composer.json file.
   *
   * @param   Object          $data  Key value object representing the composer.json
   *
   * @return  String/Boolean         JSON string, or false if failed to write
   */
  $put_composer = function($data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    // Replaces 4 space indentation with 2
    // @source https://www.php.net/manual/en/function.json-encode.php#118334
    $json = preg_replace('/^(  +?)\\1(?=[^ ])/m', '$1', $json);

    $bytes = file_put_contents(ABSPATH . '/composer.json', $json);

    return ($bytes) ? $json : $bytes;
  };

  /**
   * Strips the vendor prefixes from the composer package filenames in
   * require or require-dev
   *
   * @param   Object  $arr  A key value array of required Composer packages
   *
   * @return  Array         An array of plugin slugs
   */
  $basenames = function($arr) {
    return array_map(function($key) {
      return basename($key);
    }, array_keys($arr));
  };

  $required = false;
  $package = $get_composer();
  $require = $basenames($package['require']);
  $require_dev = $basenames($package['require-dev']);

  /**
   * For each of the updated plugins, find them in require or require-dev
   * and use the plugin's meta data to update the version number. WordPress
   * doesn't support carets or tildés in the version number.
   */

  foreach ($hook_extra['plugins'] as $plugin) {
    $dirname = dirname($plugin);

    $required = (in_array($dirname, $require)) ? 'require' : $required;
    $required = (in_array($dirname, $require_dev)) ? 'require-dev' : $required;

    if ($required) {
      $meta_data = get_plugin_data(WP_PLUGIN_DIR . "/$plugin");
      $version = $meta_data['Version'];

      $key = array_search($dirname, $require);
      $plugin = array_keys($package[$required])[$key];

      $package[$required][$plugin] = $version;
    }
  }

  /**
   * If any of the plugins are in the Composer package and were updated,
   * write to the Composer file.
   */

  if ($required) {
    $put = $put_composer($package);
  }
}, 10, 2);

/**
 * Add custom column in Plugins table to compare Composer version with
 * installed version
 */
// add_filter('manage_plugins_columns', function($columns) {
//   debug($columns);

//   return $columns;
// });
