<?php

namespace NYCO;

/**
 * Gets composer.json data and creates base name lists for comparison
 */
class Package {
  /**
   * [__construct description]
   *
   * @return  [type]  [return description]
   */
  public function __construct() {
    $this->get()->packages = array(
      'require' => $this->getBaseNames('require'),
      'require-dev' => $this->getBaseNames('require-dev'),
    );

    $this->packages['all'] = array_merge(
      $this->packages['require'],
      $this->packages['require-dev']
    );

    $this->packages['vendors'] = array();
    $this->packages['versions'] = array();

    foreach (array_merge(
      $this->data['require'],
      $this->data['require-dev']
    ) as $key => $value) {
      $this->packages['vendors'][basename($key)] = dirname($key);
      $this->packages['versions'][basename($key)] = $value;
    }

    return $this;
  }

  /**
   * [get description]
   *
   * @return  [type]  [return description]
   */
  public function get() {
    $this->data = json_decode(
      file_get_contents(ABSPATH . '/composer.json'), true
    );

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

    $required = (in_array($dirname, $this->packages['require']))
      ? 'require' : $required;

    $required = (in_array($dirname, $this->packages['require-dev']))
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
