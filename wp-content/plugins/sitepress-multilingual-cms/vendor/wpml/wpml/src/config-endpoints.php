<?php

use WPML\UserInterface\Web\Core\Component\ATE\Application\Endpoint\EnableAteController;
use WPML\UserInterface\Web\Core\Component\ATE\Application\Endpoint\GetAccountBalances\GetAccountBalancesController;
use WPML\UserInterface\Web\Core\Component\ATE\Application\Endpoint\GetGlossaryCountController;
use WPML\UserInterface\Web\Core\Component\Communication\Application\Endpoint\DismissNoticeController;
use WPML\UserInterface\Web\Core\Component\Communication\Application\Endpoint\DismissNoticePerUserController;
use WPML\UserInterface\Web\Core\Component\Communication\Application\Endpoint\GetDismissedNoticesController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetLocalTranslators\GetLocalTranslatorsController;

/**
 * ONLY USE THIS FOR GENERAL ENDPOINTS.
 * For page-specific endpoints, add the endpoint to the page config.
 *
 * Endpoint properties
 * - [arrayKey]                 Id of the endpoint.
 *  - path                      Url path to the endpoint.
 *  - method                    MethodType::* (GET, POST, PUT, DELETE)
 *                              Default: MethodType::GET
 *  - handler                   Classname of endpoint handler.
 *  - capability (optional)     Capablity string... see constants WPML_CAP_*
 *                              Default: WPML_CAP_MANAGE_TRANSLATIONS
 *
 */

return [
  'getlocaltranslators' => [
    'path'    => '/getlocaltranslators',
    'handler' => GetLocalTranslatorsController::class,
    'method'  => 'GET',
  ],
  'getaccountbalances' => [
    'path' => '/account-balances',
    'handler' => GetAccountBalancesController::class,
    'method' => 'GET',
  ],
  'getglossarycounts' => [
    'path' => '/glossary-count',
    'handler' => GetGlossaryCountController::class,
    'method' => 'GET',
  ],
  'enableate' => [
    'path' => '/enable-ate',
    'handler' => EnableAteController::class,
    'method' => 'POST',
  ],

  'getdismissednotices' => [
    'path' => '/get-dismissed-notices',
    'handler' => GetDismissedNoticesController::class,
    'method' => 'GET',
  ],
  'dismissnotice' => [
    'path' => '/dismiss-notice',
    'handler' => DismissNoticeController::class,
    'method' => 'POST',
  ],
  'dismissnoticeperuser' => [
    'path'    => '/dismiss-notice-per-user',
    'handler' => DismissNoticePerUserController::class,
    'method'  => 'POST',
  ],

];
