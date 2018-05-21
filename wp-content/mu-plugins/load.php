<?php
/**
 * Plugin Name: Autoloader
 * Description: The autoloader for 'must use' plugins. These plugins are created exclusively for ACCESS NYC for application functions. This loader includes "Config (NYCO)", "DroolsProxy (BSD)", "SendMeNYC (BSD)", and "StatCollector (BSD)"
 * Author: NYC Opportunity
 */

namespace MustUsePlugins;

const PLUGINS = [
  '/wp-config/Config.php',
  '/drools-proxy/DroolsProxy.php',
  '/sendmenyc/SendMeNYC.php',
  '/statcollector/StatCollector.php'
];

for ($i=0; $i < sizeof(PLUGINS); $i++) {
  if (file_exists(WPMU_PLUGIN_DIR . PLUGINS[$i]))
    require WPMU_PLUGIN_DIR . PLUGINS[$i];
}