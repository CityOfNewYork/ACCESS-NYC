<?php

namespace NYCO;

/**
 * Gets WordPress Plugin meta data and creates base names lists for comparison
 */
class Plugins {
  /**
   * [__construct description]
   *
   * @return  [type]  [return description]
   */
  public function __construct() {
    $this->get()->plugins = array(
      'plugins' => $this->getBaseNames('plugins'),
      'mu-plugins' => $this->getBaseNames('mu-plugins')
    );

    $this->plugins['all'] = array_merge(
      $this->plugins['plugins'],
      $this->plugins['mu-plugins']
    );

    $this->plugins['versions'] = array();

    foreach ($this->data as $key => $value) {
      if ($value['Version'] !== '') {
        $name = (strpos($key, '/')) ? dirname($key) : basename($key, '.php');

        $this->plugins['versions'][$name] = $value['Version'];
      }
    }
  }

  /**
   * [get description]
   *
   * @return  [type]  [return description]
   */
  public function get() {
    $this->data = array_merge(get_plugins(), get_mu_plugins());

    return $this;
  }

  /**
   * [getBaseNames description]
   *
   * @param   [type]   $type  [$type description]
   * @param   plugins         [ description]
   *
   * @return  [type]          [return description]
   */
  public function getBaseNames($type = 'plugins') {
    if ($type === 'mu-plugins') {
      return array_map(function($plugin) {
        return basename($plugin, '.php');
      }, array_keys(get_mu_plugins()));
    } else {
      return array_map(function($plugin) {
        return dirname($plugin);
      }, array_keys(get_plugins()));
    }
  }
}
