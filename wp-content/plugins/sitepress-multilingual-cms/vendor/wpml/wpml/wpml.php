<?php

use WPML\CompositionRoot;
use WPML\Core\Component\Communication\Application\Query\DismissedNoticesQuery;
use WPML\Infrastructure\Dic;
use WPML\Infrastructure\WordPress\Port\Persistence\Options;
use WPML\Legacy\Port\Plugin;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\Config;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\Parser;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\Updates\Controller as UpdatesController;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\Updates\UpdateHandler as UpdatesUpdateHandler;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\Updates\Repository as UpdatesRepository;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\Updates\ScriptLoader as UpdatesScriptLoader;
use WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\AdminPage;
use WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\Api;
use WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\ConfigEvents;
use WPML\Infrastructure\WordPress\Component\Communication\Domain\DismissedNoticesStorage;

if ( defined( 'WPML_VERSION' ) ) {
  // Already loaded.
  return;
}

require_once __DIR__ . '/src/constants.php';

global $sitepress;
$plugin = new Plugin( $sitepress );

$dic = new Dic();
$configArray = require_once __DIR__ . '/src/config.php';
$api = new Api();
$adminPage = new AdminPage( $api );
$updatesRepository = new UpdatesRepository( new Options(), $plugin );

$compositionRoot = new CompositionRoot(
  $dic,
  new Config(
    new Parser( $configArray ),
    $dic,
    $api,
    $adminPage,
    new UpdatesController(
      $api,
      $updatesRepository,
      new UpdatesScriptLoader( $api, $adminPage ),
      new UpdatesUpdateHandler( $api, $updatesRepository ),
      $plugin
    ),
    new DismissedNoticesQuery( new DismissedNoticesStorage() )
  ),
  new ConfigEvents( $dic )
);

// Load event listeners.
$compositionRoot->loadEventListeners();

// Admin Pages.
add_action(
  'admin_menu',
  function () use ( $compositionRoot ) {
      $compositionRoot->registerAdminPages();
  },
  1 // We must run this before legacy is doing the menu.
);

// REST Api.
add_action(
  'rest_api_init',
  function () use ( $compositionRoot ) {
      $compositionRoot->prepareUpdates();
      $compositionRoot->loadRESTEndpoints();
  }
);


// REST Api.
add_action(
  'admin_init',
  function () use ( $compositionRoot ) {
    $compositionRoot->loadAdminNotices();
    $compositionRoot->loadAjaxEndpoints();
  }
);


// Scripts for admin.
add_action(
  'admin_enqueue_scripts',
  function () use ( $compositionRoot ) {
    $compositionRoot->prepareUpdates();
    $compositionRoot->loadAdminScripts();
  }
);
