<?php

/**
 * Enqueue a client-side integration.
 *
 * @param   String   $name  Key of the integration in the mu-plugins/integrations.json
 *
 * @return  Boolean
 */
function enqueue_inline($name) {
  if (!isset($GLOBALS['wp_assets'])) {
    $GLOBALS['wp_assets'] = new NYCO\WpAssets();
  }

  if (!isset($GLOBALS['wp_integrations'])) {
    $GLOBALS['wp_integrations'] = $GLOBALS['wp_assets']->loadIntegrations();
  }

  if ($GLOBALS['wp_integrations']) {
    $index = array_search($name, array_column($GLOBALS['wp_integrations'], 'handle'));

    $GLOBALS['wp_assets']->addInline($GLOBALS['wp_integrations'][$index]);

    return true;
  } else {
    return false;
  }
}
