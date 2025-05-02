<?php

namespace WPML;

use WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Endpoint\DismissNoticeController;
use WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\StartUsingDashboardNoticeController;
use WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\ExistingPage\PostEditPage;
use WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\ExistingPage\PostListingPage;

/**
 * Notice properties
 * - [arrayKey]                 Id of the notice.
 *  - controller (optional)     Classname of the page controller.
 *                              The controller can take over specific tasks by
 *                              implementing one of the following interfaces:
 *                                - NoticeRenderInterface
 *                                  Without NoticeRenderInterface a empty
 *                                  div with the notice id is rendered.
 *                                - NoticeRequirementsInterface
 *                              Can be extended by adding further interfaces
 *                              src/UserInterface/Web/Core/SharedKernel/Config/*
 *  - onPages (optional)        Array of ExistingPageInterface.
 *                              If not set, the notice is loaded on all pages.
 *  - capability (optional)     Capability string... see constants WPML_CAP_*
 *                              Default: WPML_CAP_MANAGE_TRANSLATIONS
 *  - scripts (optional)        Array of scripts or single script.
 *   - id (optional)            Script id, if not set the page id is used.
 *   - src                      Script source path. Start with 'public/js/...'.
 *   - dependencies (optional)  Array of script dependencies.
 *   - prerequisites (optional) Classname of script prerequisites
 *                              (must implement ScriptPrerequisitesInterface).
 *   - dataProvider (optional)  Classname of data provider
 *                              (must implement ScriptDataProviderInterface).
 *  - styles (optional)         Array of styles or single style.
 *                              Can also just be a string (for src).
 *   - id (optional)            Style id, if not set the page id is used.
 *   - src                      Style source path. Start with 'public/css/...'.
 *   - dependencies (optional)  Array of style dependencies.
 *  - endpoints
 *   - [arrayKey]               Id of the endpoint.
 *    - handler                 Classname of endpoint handler.
 *    - params
 *      [arrayKey]              id of param
 *      [arrayValue]            value type of param
 *
 * Endpoint properties
 * - [arrayKey]                 Id of the endpoint.
 *  - path                      Url path to the endpoint.
 *  - method                    MethodType::* (GET, POST, PUT, DELETE)
 *                              Default: MethodType::GET
 *  - handler                   Classname of endpoint handler.
 *  - capability (optional)     Interface of capability
 *                              Notice capability is used if not set.
 *
 *
 * HOW TO DISMISS A NOTICE:
 * ```js
 *  import { useDispatch } from 'react-redux'
 *  import { dismissNoticeAction } from '@wpml/shared/Store/Communication/Action/dismissNoticeAction'
 *  ...
 *  const dispatch = useDispatch()
 *  <button onClick={ function() { dispatch( dismissNoticeAction( 'notice-id' ) ) } }>Dismiss</button>
 *  ...
 * ```
 */
return [
  'wpml-start-using-dashboard-notice' => [
    'controller' => StartUsingDashboardNoticeController::class,
    'onPages'    => [ PostListingPage::class, PostEditPage::class ],
    'scripts'    => [
      [
        'id'            => 'notice-promote-using-dashboard',
        'src'           => 'public/js/notice-promote-using-dashboard.js',
        'dependencies'  => [ 'wpml-node-modules', 'wp-i18n', 'lodash' ],
        'prerequisites' => StartUsingDashboardNoticeController::class,
        'dataProvider'  => StartUsingDashboardNoticeController::class,
      ],
    ],
    'styles'     => [
      'src'          => 'public/css/notice-promote-using-dashboard.css',
      'dependencies' => [ 'otgs-icons' ]
    ],
    'endpoints'  => [
      'dismissusetmdashboardnotice' => [
        'path'    => '/usetmdashboardnotice/dismiss',
        'handler' => DismissNoticeController::class,
      ],
    ],
  ],
];
