<?php

namespace NYCO\QueryMonitor;

use NYCO\QueryMonitor as Qm;

/**
 * Query Monitor Add On. Currently displays integrations available to the site.
 * Model for this add on is based heavily on the other examples in the source.
 *
 * @source https://github.com/johnbillion/query-monitor/wiki/Query-Monitor-Add-on-Plugins
 */
class WpAssetsAddOn {
  /**
   * Constructor. Adds tab to Query Monitor panel
   *
   * @return  Object  Instance of self WpAssetsAddOn
   */
  public function __construct($WpAssets) {
    /**
     * Construct add on after the Query Monitor plugin has
     * been loaded and if Query Monitor is an active plugin
     */
    add_action('plugins_loaded', function() use ($WpAssets) {
      if (class_exists('QM_Collectors')) {
        require_once plugin_dir_path(__FILE__) . 'WpAssetsCollector.php';

        $this->QM_Collector = new Qm\WpAssetsCollector($WpAssets);

        $this->id = $this->QM_Collector->id;

        $this->name = $this->QM_Collector->name();

        /**
         * Add Query Monitor Hooks. The main hooks is the collector and the output
         */

        add_filter('qm/collectors', array($this, 'qmCollectors'), 20, 1);
        add_filter('qm/outputter/html', array($this, 'qmOutputterHtml'));
      }
    });

    return $this;
  }

  /**
   * Add a Query Monitor Collector
   *
   * @param   Array   $collectors  All Query Monitor Collectors
   *
   * @return  Array                All Query Monitor Collectors
   */
  public function qmCollectors($collectors) {
    $collectors[$this->id] = $this->QM_Collector;

    return $collectors;
  }

  /**
   * Add menu item and panel output for our Collector
   *
   * @param   Array   $output  All QM Collector outputs
   *
   * @return  Array            All QM Collector outputs
   */
  public function qmOutputterHtml($output) {
    require_once plugin_dir_path(__FILE__) . 'WpAssetsOutput.php';

    $collector = \QM_Collectors::get($this->id);

    if ($collector instanceof Qm\WpAssetsCollector) {
      $output[$this->id] = new Qm\WpAssetsOutput($collector);
    }

    return $output;
  }
}
