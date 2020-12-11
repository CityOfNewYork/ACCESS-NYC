<?php

/**
 * Reads/Writes composer.json and stores basename arrays for comparison
 */
class Package {
  public function __construct() {
    $this->get()->packages = array(
      'require' => $this->getBaseNames('require'),
      'require-dev' => $this->getBaseNames('require-dev'),
    );

    $this->packages['all'] = array_merge(
      $this->packages['require'],
      $this->packages['require-dev']
    );

    return $this;
  }

  /**
   * [get description]
   *
   * @return  [type]  [return description]
   */
  public function get() {
    $this->data = json_decode(
      file_get_contents(ABSPATH . '/composer.json'), true);

    return $this;
  }

  /**
   * Strips the vendor prefixes from the composer package filenames in
   * require or require-dev
   *
   * @param   Object  $arr  A key value array of required Composer packages
   *
   * @return  Array         An array of plugin slugs
   */
  public function getBaseNames($require) {
    return array_map(function($key) {
      return basename($key);
    }, array_keys($this->data[$require]));
  }

  /**
   * [required description]
   *
   * @param   [type]  $plugin    [$plugin description]
   * @param   [type]  $package   [$package description]
   * @param   [type]  $filename  [$filename description]
   *
   * @return  [type]             [return description]
   */
  public function required($plugin, $filename = true) {
    $dirname = ($filename) ? dirname($plugin) : $plugin;

    $required = false;

    $required = (in_array($dirname, $this->baseNames['require']))
      ? 'require' : $required;

    $required = (in_array($dirname, $this->baseNames['require-dev']))
      ? 'require-dev' : $required;

    return $required;
  }

  /**
   * [write description]
   *
   * @return  [type]  [return description]
   */
  public function write() {
    $json = json_encode($this->json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    // Replaces 4 space indentation with 2
    // @source https://www.php.net/manual/en/function.json-encode.php#118334
    $this->json = preg_replace('/^(  +?)\\1(?=[^ ])/m', '$1', $json);

    $this->bytes = file_put_contents(ABSPATH . '/composer.json', $json);

    return $this;
  }
}

/**
 * Gets WordPress Plugin data and creates basenames for comparision
 */
class Plugins {
  public function __construct() {
    $this->get();

    $this->plugins = array(
      'plugins' => array_map(function($plugin) {
          return dirname($plugin);
        }, array_keys(get_plugins())),
      'mu-plugins' => array_map(function($plugin) {
          return basename($plugin, '.php');
        }, array_keys(get_mu_plugins()))
    );

    $this->plugins['all'] = array_merge(
      $this->plugins['plugins'],
      $this->plugins['mu-plugins']
    );
  }

  public function get() {
    $this->data = array_merge(get_plugins(), get_mu_plugins());

    return $this;
  }
}

/**
 * Plugin. Syncs WP Plugin and Composer data
 */
class WpComposerSync {
  public function __construct() {
    $this->package = new Package();
    $this->plugins = new Plugins();

    // Which packages are plugins
    $this->packages['plugins'] = array_map(function($plugin) {
      return array(
        'name' => $plugin,
        'must-use' => in_array($plugin, $this->plugins['mu-plugins']),
        'require-dev' => in_array($plugin, $this->packages['require-dev']),
        // composer vendor prefix (vendor + plugin name)
        // current version
        // composer required version
        // available updates
      );
    }, array_intersect(
      $this->packages['all'],
      $this->plugins['all']
    ));

    add_action('upgrader_process_complete', [$this, 'upgraderProcessComplete'], 10, 2);
    add_filter('manage_plugins_columns', [$this, 'managePluginsColumns']);
    add_filter('manage_plugins_custom_column', [$this, 'managePluginsCustomColumn'], 10, 3);
  }

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
  function upgraderProcessComplete($WP_Upgrader, $hook_extra) {
    /**
     * Only trigger if this is a plugin update
     */

    if ('plugin' !== $hook_extra['type'] || 'update' !== $hook_extra['action']) {
      return;
    }

    $required = false;
    $package = $this->package->json;

    /**
     * For each of the updated plugins, find them in require or require-dev
     * and use the plugin's meta data to update the version number. WordPress
     * doesn't support carets or tildés in the version number.
     */

    foreach ($hook_extra['plugins'] as $plugin) {
      // $dirname = dirname($plugin);

      // $required = (in_array($dirname, $require)) ? 'require' : $required;
      // $required = (in_array($dirname, $require_dev)) ? 'require-dev' : $required;
      $required = $this->package->required($plugin);

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
      $put = put_composer($package);
      $this->package->write();
    }
  }

  /**
   * Add version columns in Plugins table to compare Composer package version
   * with installed version
   *
   * @param   [type]  $columns  [$columns description]
   *
   * @return  [type]            [return description]
   */
  public function managePluginsColumns($columns) {
    return array_merge(
      array_slice($columns, 0, 4),
      array(
        'version' => __('Version'), // Add Version Column
        'composer' => __('Composer') // Add Composer Column
      ),
      array_slice($columns, 4)
    );
  }

  /**
   * [$output description]
   *
   * @param   [type]  $column_name  [$output description]
   * @param   [type]  $plugin_file  [$column_name description]
   * @param   [type]  $plugin_data  [$user_id description]
   *
   * @return  [type]                [return description]
   */
  function managePluginsCustomColumn($column_name, $plugin_file, $plugin_data) {
    if ($column_name === 'version') {
      echo $plugin_data['Version'];
    }

    if ($column_name === 'composer') {
      // Composer Version
      debug($this->package);
    }
  }
}

new WpComposerSync();
