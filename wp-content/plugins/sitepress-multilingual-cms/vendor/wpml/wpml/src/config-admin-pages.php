<?php

namespace WPML;

use WPML\UserInterface\Web\Core\Component\Dashboard\Application\DashboardController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\DashboardRequirements;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetCredits\GetCreditsController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetHierarchicalPosts\GetHierarchicaPostsController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetLocalTranslatorById\GetTranslatorByIdController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetNeedsUpdateCreatedInCte\GetNeedsUpdateCreatedInCteController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPopulatedItemSections\GetPopulatedItemSectionsController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPostTaxonomies\GetPostTaxonomiesController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPostTerms\GetPostTermsController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts\GetPostControllerInterface;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts\GetPostsCountController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetRemoteTranslationService\GetRemoteTranslationServiceController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetTranslationBatchDefaultName\GetTranslationBatchDefaultName;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetTranslationEditorType\GetTranslationEditorTypeController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetTranslationStatus\GetTranslationStatusController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetUntranslatedTypesCount\GetUntranslatedTypesCountController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\SaveTranslatorNote\SaveTranslatorNoteController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\SendToTranslation\SendToTranslationController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\SetReviewTranslationOption\SetReviewTranslationOptionController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\TranslateEverything\DisableController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\TranslateEverything\EnableController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\TranslateEverything\TranslateExistingContentController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\TranslationProxy\GetLastPickedUpController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\TranslationProxy\GetRemoteJobsCountController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\TranslationProxy\SendCommitRequestController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\ValidateSelectedTranslationMethods\ValidateSelectedTranslationMethodsController;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\ValidateTranslationBatchName\ValidateTranslationBatchNameController;
use WPML\UserInterface\Web\Core\Component\Preferences\Application\AutomaticTranslationsSectionController;
use WPML\UserInterface\Web\Core\Component\Preferences\Application\Endpoint\GetEngines\GetEnginesController;
use WPML\UserInterface\Web\Core\Component\Preferences\Application\Endpoint\SaveAutomaticTranslationsSettings\SaveAutomaticTranslationsSettingsController;

/**
 * Page properties
 * - [arrayKey]                 Id of the page.
 *  - parentId (optional)       To create a sub page.
 *  - legacyParentId (optional) To create a sub page in a legacy menu.
 *  - title (optional)          Title of the page.
 *                              Default: ''
 *  - controller (optional)     Classname of the page controller.
 *                              The controller can take over specific tasks by
 *                              implementing one of the following interfaces:
 *                                - PageRenderInterface
 *                                - PageConfigUserInterface
 *                                - PageRequirementsInterface
 *                              Can be extended by adding further interfaces
 *                              src/UserInterface/Web/Core/SharedKernel/Config/*
 *  - requirements (optional)   Classname to specify the page loading requirements.
 *                              It must implement PageRequirementsInterface.
 *  - menuTitle (optional)      Title of the menu item.
 *                              Default: [value of title]
 *  - capability (optional)     Capability string... see constants WPML_CAP_*
 *                              Default: WPML_CAP_MANAGE_TRANSLATIONS
 *  - legacyExtension(optional) Name of action to load the page scripts, styles and endpoints.
 *                              WARNING: This will disable the page and page menu registration.
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
 *                              Page capability is used if not set.
 */
return [
  'tm/menu/main.php' => [ // Keeping the old id 'tm/menu/main.php' as long as not all tabs are migrated from legacy.
    'title' => __( 'Translation Management', 'wpml' ),

    'controller' => DashboardController::class,

    'legacyParentId' => 'WPML',
    'position'       => 1,

    'requirements' => DashboardRequirements::class,

    // If more than one script is needed a multi array can be used.
    'scripts'         => [
      [
        'id'            => 'wpml-dashboard',
        'src'           => 'public/js/dashboard.js',
        'prerequisites' => DashboardController::class,
        'dataProvider'  => DashboardController::class,
        'dependencies'  => [ 'wpml-node-modules', 'wp-i18n', 'lodash' ]
      ],
      [ // Move 'wpml-notice-glossary' to config-admin-notices.php
        'id'            => 'wpml-notice-glossary',
        'src'           => 'public/js/notice-glossary.js',
        'prerequisites' => DashboardController::class,
        'dependencies'  => [ 'wpml-node-modules', 'wp-i18n', 'lodash' ]
      ],
    ],

    // If there is only one style for the page and it has no
    // dependencies, it can be defined simply like this:
    'styles'         => [
      'src'          => 'public/css/dashboard.css',
      'dependencies' => [ 'otgs-icons' ]
    ],

    // Endpoints only used by this page.
    'endpoints'      => [
      'getpopulateditemsections' => [
        'path' => '/item-sections/populated',
        'handler' => GetPopulatedItemSectionsController::class,
      ],
      'getposts'                   => [
        'path'    => '/posts',
        'handler' => GetPostControllerInterface::class,
      ],
      'getpostscount'              => [
        'path'    => '/posts/count',
        'handler' => GetPostsCountController::class,
      ],
      'sendtotranslation'          => [
        'path'    => '/send-to-translation',
        'handler' => SendToTranslationController::class,
        'method'  => 'POST',
        'useAjax' => true,
      ],
      'validatetranslationoptions' => [
        'path'    => '/send-to-translation/validate-translation-options',
        'handler' => ValidateSelectedTranslationMethodsController::class,
        'method'  => 'POST',
      ],
      'getdefaultbatchname'        => [
        'path'    => '/send-to-translation/default-batch-name',
        'handler' => GetTranslationBatchDefaultName::class,
        'method'  => 'GET',
      ],
      'validatebatchname'          => [
        'path'    => '/send-to-translation/validate-batch-name',
        'handler' => ValidateTranslationBatchNameController::class,
        'method'  => 'POST',
      ],
      'setreviewtranslationoption' => [
        'path'    => '/set-review-translation-option',
        'handler' => SetReviewTranslationOptionController::class,
        'method'  => 'POST',
      ],
      'gethierarchicalposts'       => [
        'path'    => '/posts/hierarchical',
        'handler' => GetHierarchicaPostsController::class,
      ],
      'getposttaxonomies'          => [
        'path'    => '/posts/taxonomies',
        'handler' => GetPostTaxonomiesController::class,
      ],
      'getpostterms'               => [
        'path'    => '/posts/terms',
        'handler' => GetPostTermsController::class,
      ],
      'enabletranslateeverything'  => [
        'path'    => '/translate-everything/enable',
        'handler' => EnableController::class,
        'method'  => 'POST',
      ],
      'disabletranslateeverything' => [
        'path'    => '/translate-everything/disable',
        'handler' => DisableController::class,
        'method'  => 'POST',
      ],
      'getuntranslatedtypescount'  => [
        'path'    => '/getuntranslatedtypescount',
        'handler' => GetUntranslatedTypesCountController::class,
        'method'  => 'GET',
      ],
      'savetranslatornote'         => [
        'path'    => '/save-translator-note',
        'handler' => SaveTranslatorNoteController::class,
        'method'  => 'POST',
      ],
      'getcredits'                 => [
        'path'    => '/credits',
        'handler' => GetCreditsController::class,
        'method'  => 'GET',
      ],
      'committotranslationproxy'   => [
        'path'    => '/translation-proxy/commit-batch',
        'handler' => SendCommitRequestController::class,
        'method'  => 'POST',
      ],
      'getlastpickedup'            => [
        'path'    => '/tranlsation-proxy/getlastpickedup',
        'handler' => GetLastPickedUpController::class,
        'method'  => 'GET',
      ],
      'getremotejobscount'         => [
        'path'    => '/translation-proxy/getRemoteJobsCount',
        'handler' => GetRemoteJobsCountController::class,
        'method'  => 'GET',
      ],
      'gettranslationstatus'       => [
        'path'    => '/gettranslationstatus',
        'handler' => GetTranslationStatusController::class,
        'method'  => 'GET',
      ],
      'getlocaltranslatorbyid' => [
        'path'    => '/getlocaltranslatorbyid',
        'handler' => GetTranslatorByIdController::class,
        'method'  => 'GET',
      ],
      'reloadremotetranslationservice' => [
        'path'    => '/reloadremotetranslationservice',
        'handler' => GetRemoteTranslationServiceController::class,
        'method'  => 'GET',
      ],
      'gettranslationeditortype' => [
        'path'    => '/gettranslationeditortype',
        'handler' => GetTranslationEditorTypeController::class,
        'method'  => 'GET',
        ],

      'translateexistingcontent' => [
        'path'    => '/translate-existing-content',
        'handler' => TranslateExistingContentController::class,
        'method'  => 'POST',
      ],
      'getneedsupdatecountcreatedincte' => [
        'path'    => '/get-needs-update-count-created-in-cte',
        'handler' => GetNeedsUpdateCreatedInCteController::class,
        'method'  => 'GET',
      ],
      'getengines' => [
        'path'    => '/get-engines',
        'handler' => GetEnginesController::class,
        'method'  => 'GET',
      ],
    ],
  ],
  'automatic-translations-settings' => [
    'title' => __( 'Automatic translation settings', 'wpml' ),

    'controller'    => AutomaticTranslationsSectionController::class,
    'prerequisites' => AutomaticTranslationsSectionController::class,
    'dependencies'  => [ 'wpml-node-modules', 'wp-i18n', 'lodash' ],

    'legacyExtension' => 'load-wpml_page_tm/menu/settings',

    'scripts'         => [
      [
        'id'            => 'wpml-automatic-translations-settings',
        'src'           => 'public/js/automatic-translations-settings.js',
        'prerequisites' => AutomaticTranslationsSectionController::class,
        'dataProvider'  => AutomaticTranslationsSectionController::class,
        'dependencies'  => [ 'wpml-node-modules', 'wp-i18n', 'lodash' ]
      ],
    ],

    'styles' => [
      'src'          => 'public/css/automatic-translations-settings.css',
      'dependencies' => []
    ],

    'endpoints' => [
      'getengines' => [
        'path'    => '/get-engines',
        'handler' => GetEnginesController::class,
        'method'  => 'GET',
      ],
      'saveautomatictranslationsettings' => [
        'path'    => '/save-automatic-translation-settings',
        'handler' => SaveAutomaticTranslationsSettingsController::class,
        'method'  => 'POST',
      ],
    ],
  ],
];
