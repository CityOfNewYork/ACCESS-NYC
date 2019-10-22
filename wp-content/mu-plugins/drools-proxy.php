<?php

/**
 * Plugin Name: Drools Proxy
 * Description: Backend Proxy for Drools web requests.
 * Author: Blue State Digital
 */

if (file_exists(plugin_dir_path(__FILE__) . '/drools-proxy/DroolsProxy.php')) {
  require plugin_dir_path(__FILE__) . '/drools-proxy/DroolsProxy.php';
}
