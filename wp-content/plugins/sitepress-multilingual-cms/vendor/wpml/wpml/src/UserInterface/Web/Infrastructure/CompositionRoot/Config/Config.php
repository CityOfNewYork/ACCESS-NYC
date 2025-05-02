<?php

namespace WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config;

use WPML\ConfigInterface;
use WPML\Core\Component\Communication\Application\Query\DismissedNoticesQuery;
use WPML\DicInterface;
use WPML\PHP\Exception\Exception;
use WPML\UserInterface\Web\Core\Port\Script\ScriptDataProviderInterface;
use WPML\UserInterface\Web\Core\Port\Script\ScriptPrerequisitesInterface;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Endpoint\Endpoint;
use WPML\UserInterface\Web\Core\SharedKernel\Config\ExistingPageInterface;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Notice;
use WPML\UserInterface\Web\Core\SharedKernel\Config\NoticeRequirementsInterface;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Page;
use WPML\UserInterface\Web\Core\SharedKernel\Config\PageRequirementsInterface;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Script;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Style;

class Config implements ConfigInterface {

  /** @var Parser $parser */
  private $parser;

  /** @var DicInterface $dic */
  private $dic;

  /** @var ApiInterface $api */
  private $api;

  /** @var PageInterface $page */
  private $page;

  /** @var UpdatesHandlerInterface $updatesHandler */
  private $updatesHandler;

  /** @var DismissedNoticesQuery $noticesQuery */
  private $noticesQuery;

  /** @var string[]|null $_noticesDismissed */
  private $_noticesDismissed;


  public function __construct(
    Parser $config,
    DicInterface $dic,
    ApiInterface $api,
    PageInterface $page,
    UpdatesHandlerInterface $update,
    DismissedNoticesQuery $dismissedNoticesQuery
  ) {
    $this->parser = $config;
    $this->dic = $dic;
    $this->api = $api;
    $this->page = $page;
    $this->updatesHandler = $update;
    $this->noticesQuery = $dismissedNoticesQuery;
  }


  /** @return void */
  public function loadRESTEndpoints() {
    $config = $this->parser->parseAllRESTEndpoints();

    foreach ( $config->endpoints() as $endpoint ) {
      new EndpointLoader( $endpoint, $this->dic, $this->api );
    }
  }


  /** @return void */
  public function loadAjaxEndpoints() {
    $config = $this->parser->parseAllAjaxEndpoints();

    foreach ( $config->endpoints() as $endpoint ) {
      new EndpointLoader( $endpoint, $this->dic, $this->api );
    }
  }


  /** @return array<class-string,class-string> */
  public function getInterfaceMappings() {
    return $this->parser->parseInterfaceMappings();
  }


  /** @return array<class-string,array<string,string>> */
  public function getClassDefinitions() {
    return $this->parser->parseClassDefinitions();
  }


  /**
   * @throws Exception|\InvalidArgumentException
   * @return void
   */
  public function registerAdminPages() {
    $config = $this->parser->parseAdminPages();
    foreach ( $config->adminPages() as $adminPage ) {
      if ( $this->pageRequirementsMet( $adminPage ) ) {
        $this->page->register( $adminPage, [ $this, 'onLoadPage' ] );
      } else {
        // If requirements are not matched, remove the Page from config
        // to avoid registering Ajax & REST endpoints later.
        $this->parser->removeAdminPageConfig( $adminPage->id() );
      }
    }
  }


  /**
   * @throws Exception|\InvalidArgumentException
   * @return void
   */
  public function loadAdminNotices() {
    $config = $this->parser->parseAdminNotices();
    foreach ( $config->adminNotices() as $adminNotice ) {
      if ( $this->noticeRequirementsMet( $adminNotice ) && ! $this->isNoticeDismissed( $adminNotice ) ) {
        $this->loadNotice( $adminNotice );
      } else {
        // If requirements are not matched, remove the Page from config
        // to avoid registering Ajax & REST endpoints later.
        $this->parser->removeAdminNoticeConfig( $adminNotice->id() );
      }
    }
  }


  /**
   * @throws Exception
   * @return void
   */
  public function loadAdminScripts() {
    $config = $this->parser->parseScripts();
    foreach ( $config->scripts() as $script ) {
      if ( ! $script->usedOnAdmin() ) {
        continue;
      }
      $script->onlyRegister()
        ? $this->page->registerScript( $script )
        : $this->page->loadScript( $script );
    }
  }


  /** @return void */
  public function prepareUpdates() {
    $updates = $this->parser->parseUpdates();
    $dic = $this->dic;
    foreach ( $updates as $update ) {
      $update->setCreateHandler(
        function () use ( $update, $dic ) {
          return $dic->make( $update->handlerClassName() );
        }
      );
    }
    $this->updatesHandler->prepareUpdates( $updates );
  }


  /** @return void */
  public function onLoadPage( Page $page ) {
    $this->initPage( $page );
    $this->loadScripts( $page->scripts() );
    $this->provideEndpoints(
      $page->endpoints(),
      $this->firstScriptOrNull( $page->scripts() )
    );
    $this->loadStyles( $page->styles() );
  }


  /** @return ?object */
  private function initPage( Page $page ) {
    $controllerClassName = $page->controllerClassName();
    if ( ! $controllerClassName ) {
      // Nothing to init.
      return null;
    }

    $controller = $this->dic->make( $controllerClassName );
    $page->setController( $controller );

    return $controller;
  }


  /** @return void */
  public function loadNotice( Notice $notice ) {
    $this->initAdminNotice( $notice );

    $this->loadScripts( $notice->scripts() );
    $this->provideEndpoints(
      $notice->endpoints(),
      $this->firstScriptOrNull( $notice->scripts() )
    );
    $this->loadStyles( $notice->styles() );

    if ( $pageToRenderNotice = $notice->onPageActive() ) {
      $pageToRenderNotice->renderNotice( $notice );
    } else {
      $notice->render();
    }
  }


  /** @return ?object */
  private function initAdminNotice( Notice $notice ) {
    $controllerClassName = $notice->controllerClassName();

    if ( ! $controllerClassName ) {
      return null;
    }

    $controller = $this->dic->make( $controllerClassName );
    $notice->setController( $controller );

    return $controller;
  }


  /**
   * @param array<Style> $styles
   * @return void
   */
  private function loadStyles( $styles ) {
    foreach ( $styles as $style ) {
      $this->page->loadStyle( $style );
    }
  }


  /**
   * @param array<Script> $scripts
   * @return void
   */
  private function loadScripts( $scripts ) {
    foreach ( $scripts as $script ) {
      if ( $scriptPrerequisitesClass = $script->prerequisites() ) {
        /** @var ScriptPrerequisitesInterface $ */
        $scriptPrerequisites = $this->dic->make( $scriptPrerequisitesClass );

        if ( ! $scriptPrerequisites instanceof ScriptPrerequisitesInterface ) {
          throw new \InvalidArgumentException(
            'Invalid script prerequisites. It must implement ' .
            ScriptPrerequisitesInterface::class
          );
        }

        if ( ! $scriptPrerequisites->scriptPrerequisitesMet() ) {
          // Skip loading the script as prerequisites are not met.
          continue;
        }
      }

      $this->page->loadScript( $script );

      // Check if there is a dataProvider defined in the config.
      if ( $dataProviderClass = $script->dataProvider() ) {
        /** @var ScriptDataProviderInterface $dataProvider */
        $dataProvider = $this->dic->make( $dataProviderClass );

        if ( ! $dataProvider instanceof ScriptDataProviderInterface ) {
          throw new \InvalidArgumentException(
            'Invalid data provider. It must implement ' .
                    ScriptDataProviderInterface::class
          );
        }

        $this->page->provideDataForScript(
          $script,
          $dataProvider->jsWindowKey(),
          $dataProvider->initialScriptData()
        );
      }
    }
  }


  /**
   * @param Endpoint[] $endpoints
   * @param ?Script $scriptForData
   *
   * @return void
   */
  private function provideEndpoints( $endpoints, $scriptForData = null ) {
    // Provide endpoints in wpmlEndpoints.
    $wpmlEndpoints = [
      'route' => [],
      'nonce' => $this->api->nonce()
    ];

    $config = $this->parser->parseGeneralEndpoints();
    /** @var array<Endpoint> $endpoints */
    $endpoints = array_merge(
      $config->endpoints(),
      $endpoints
    );

    foreach ( $endpoints as $endpoint ) {
      $endpointToArray = [];
      $endpointToArray['url'] = $this->api->getFullUrl( $endpoint );
      $wpmlEndpoints['route'][ $endpoint->id() ] = $endpointToArray;
    }

    if ( count( $wpmlEndpoints['route'] ) === 0 ) {
      return;
    }

    if ( $scriptForData === null ) {
      // phpcs:ignore
      error_log(
        'WordPress Limitiation: There must be at least one script attached ' .
        'to the page to use provideDataForScript().'
      );

      return;
    }

    $this->page->provideDataForScript(
      $scriptForData,
      'wpmlEndpoints',
      $wpmlEndpoints
    );
  }


  /**
   * @throws \InvalidArgumentException
   */
  private function pageRequirementsMet( Page $page ): bool {
    if ( $requirementsClassName = $page->requirementsClassName() ) {
      $requirements = $this->dic->make( $requirementsClassName );

      if ( ! $requirements instanceof PageRequirementsInterface ) {
        throw new \InvalidArgumentException(
          'Invalid Page Requirements Class. It must implements ' . PageRequirementsInterface::class
        );
      }

      return $requirements->requirementsMet();
    }

    if ( $controllerClassName = $page->controllerClassName() ) {
      $controller = $this->dic->make( $controllerClassName );

      if ( $controller instanceof PageRequirementsInterface ) {
        return $controller->requirementsMet();
      }
    }

    return true;
  }


  /**
   * @throws \InvalidArgumentException
   */
  private function noticeRequirementsMet( Notice $notice ): bool {
    if ( $controllerClassName = $notice->controllerClassName() ) {
      $controller = $this->dic->make( $controllerClassName );

      if (
        $controller instanceof NoticeRequirementsInterface
        && ! $controller->requirementsMet()
      ) {
        // Abort if the controllers requirements are not met.
        return false;
      }
    }

    $existingPages = $notice->onPages();
    if ( empty( $existingPages ) ) {
      // No pages are defined, so the notice should be displayed.
      return true;
    }

    foreach ( $existingPages as $existingPageClassName ) {
      $existingPage = $this->dic->make( $existingPageClassName );
      if (
        $existingPage instanceof ExistingPageInterface
        && $existingPage->isActive()
      ) {
        $notice->setOnPageActive( $existingPage );
        return true;
      }
    }

    // Pages are defined, but none of them is active.
    return false;
  }


  /**
   * @param array<Script> $scripts
   * @return ?Script
   */
  private function firstScriptOrNull( $scripts ) {
    return count( $scripts ) > 0 ? array_values( $scripts )[0] : null;
  }


  /**
   * @return bool
   */
  private function isNoticeDismissed( Notice $notice ) {
    if ( $this->_noticesDismissed === null ) {
      $this->_noticesDismissed = $this->noticesQuery->getDismissed();
    }

    return in_array( $notice->id(), $this->_noticesDismissed, true );
  }


}
