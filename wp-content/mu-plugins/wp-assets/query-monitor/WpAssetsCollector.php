<?php

namespace NYCO\QueryMonitor;

class WpAssetsCollector extends \QM_Collector {
  /** @var  String  Query monitor add-on panel ID */
  public $id = 'nyco-wp-assets';

  /** @var  String  Query monitor add-on panel name */
  public $name = 'NYCO WP Assets';

  /**
   * Constructor. Sets instance of WpAssets to self
   *
   * @param   Object  $WpAssets  Instance of WpAssets
   *
   * @return  Object             Instance of self (WpAssetsCollector)
   */
  public function __construct($WpAssets) {
    $this->WpAssets = $WpAssets;

    add_action('wp_print_scripts', [$this, 'wpPrintScripts'], 9999);

    return $this;
  }

  /**
   * Action to collect registered integrations and queue position
   */
  public function wpPrintScripts() {
    global $wp_scripts;

    $this->data['queue'] = $wp_scripts->queue;
    $this->data['registered'] = $wp_scripts->registered;
  }

  // phpcs:disable
  /**
   * Return WordPress Actions the add-on is concerned with
   *
   * @return  Array  List of WordPress actions
   */
  public function get_concerned_actions() {
    return [
      'wp_print_scripts'
    ];
  }
  // phpcs:enable

  // phpcs:disable
  /**
   * Return WordPress Filters the add-on is concerned with
   *
   * @return  Array  List of WordPress actions
   */
  public function get_concerned_filters() {
    return [];
  }
  // phpcs:enable

  /**
   * Get the add-on name
   *
   * @return  String  The add-on name
   */
  public function name() {
    return __($this->name);
  }

  /**
   * Set data to the collector
   *
   * @return  Array  Data for the output
   */
  public function process() {
    $integrations = $this->WpAssets->loadIntegrations();

    // Collect the integration and it's details
    $this->data['integrations'] = ($integrations) ?
      array_map(function($int) {
        $handle = $int['handle'];

        // Get the final registered output
        $int['registered'] = array_key_exists($handle, $this->data['registered']) ?
          $this->data['registered'][$handle] : false;

        // Get the index of the integration in the queue
        $int['queue'] = (in_array($handle, $this->data['queue'])) ?
          array_search($handle, $this->data['queue']) : false;

        return $int;
      }, $integrations) : array();

    return $this->data;
  }
}
