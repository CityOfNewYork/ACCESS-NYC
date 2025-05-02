<?php

namespace WPML;

/**
 * Single entry for building whole project.
 */
class CompositionRoot {

  /** @var DicInterface */
  private $dic;

  /** @var ConfigInterface */
  private $config;

  /** @var ConfigEventsInterface */
  private $configEvents;


  /**
   * CompositionRoot constructor.
   */
  public function __construct(
    DicInterface $dic,
    ConfigInterface $config,
    ConfigEventsInterface $configEvents
  ) {
    $this->dic          = $dic;
    $this->config       = $config;
    $this->configEvents = $configEvents;

    $this->defineShares();
    $this->defineAliases();
    $this->defineClasses();
  }


  /** @return void */
  public function loadRESTEndpoints() {
    $this->config->loadRESTEndpoints();
  }


  /** @return void */
  public function loadAjaxEndpoints() {
    $this->config->loadAjaxEndpoints();
  }


  /** @return void */
  public function registerAdminPages() {
    $this->config->registerAdminPages();
  }


  /** @return void */
  public function loadAdminNotices() {
    $this->config->loadAdminNotices();
  }


  /** @return void */
  public function loadEventListeners() {
    $this->configEvents->loadEvents();
  }


  /** @return void */
  public function loadAdminScripts() {
    $this->config->loadAdminScripts();
  }


  /** @return void */
  public function prepareUpdates() {
    $this->config->prepareUpdates();
  }


  /**
   * List of all common interface implementations.
   *
   * @return void
   */
  private function defineAliases() {
    foreach ( $this->config->getInterfaceMappings() as $interface => $class ) {
      $this->dic->alias( $interface, $class );
    }
  }


  /**
   * List defintions per class. Usefull for classes which are not using the
   * general alias of an interface.
   *
   * @return void
   */
  private function defineClasses() {
    foreach ( $this->config->getClassDefinitions() as $class => $args ) {
      if ( is_callable( $args ) ) {
        /**
         * I wrap the original factory method to pass down the DIC to the factory.
         *
         * @psalm-suppress MissingClosureReturnType
         * @return mixed
         */
        $wrappedFactory = function () use ( $args ) {
          return call_user_func( $args, $this->dic );
        };

        $this->dic->delegate( $class, $wrappedFactory );
      } else {
        $this->dic->define( $class, $args );
      }
    }
  }


  /**
   * List of classes / objects used as singletons by the DIC.
   *
   * @return void
   */
  private function defineShares() {
    // @phpcs:ignore
    global $wpdb, $sitepress;
    $this->dic->share( $wpdb );
    $this->dic->share( $sitepress );
    $this->dic->defineParam( 'wpdb', $wpdb );
    $this->dic->defineParam( 'sitepress', $sitepress );
  }


}
