<?php

namespace WPML\UserInterface\Web\Core\SharedKernel\Config;

use WPML\PHP\Exception\Exception;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Endpoint\Endpoint;

class Notice {

  /** @var string $id */
  private $id;

  /** @var ?class-string $controllerClassName */
  private $controllerClassName;

  /** @var ?object $controller */
  private $controller;

  /** @var array<class-string> $onPages */
  private $onPages = [];

  /** @var ?ExistingPageInterface $onPageActive */
  private $onPageActive;

  /** @var ?string $capability */
  private $capability;

  /** @var array<Script> $scripts */
  private $scripts = [];

  /** @var array<Style> $styles */
  private $styles = [];

  /** @var array<Endpoint> $endpoints */
  private $endpoints = [];


  public function __construct( string $id ) {
    $this->id = $id;
  }


  public function id(): string {
    return $this->id;
  }


  /** @return ?class-string */
  public function controllerClassName() {
    return $this->controllerClassName;
  }


  /**
   * @param class-string $controllerClassName
   * @return static
   */
  public function setControllerClassName( $controllerClassName ) {
    $this->controllerClassName = $controllerClassName;
    return $this;
  }


  /**
   * @param object $controller
   * @return static
   */
  public function setController( $controller ) {
    $this->controller = $controller;
    return $this;
  }


  /**
   * @return array<class-string>
   */
  public function onPages() {
    return $this->onPages;
  }


  /**
   * @param class-string $page
   * @return static
   */
  public function addOnPage( $page ) {
    $this->onPages[] = $page;
    return $this;
  }


  /** @return ?ExistingPageInterface */
  public function onPageActive() {
    return $this->onPageActive;
  }


  /**
   * @return static
   */
  public function setOnPageActive( ExistingPageInterface $page ) {
    $this->onPageActive = $page;
    return $this;
  }


  public function capability(): string {
    return $this->capability ?? WPML_CAP_MANAGE_TRANSLATIONS;
  }


  /** @return static */
  public function setCapability( string $capability ) {
    $this->capability = $capability;
    return $this;
  }


  /** @return void */
  public function render() {
    if ( $this->controller instanceof NoticeRenderInterface ) {
      $this->controller->render();
      return;
    }

    echo $this->getHtmlScriptRootContainers();
  }


  public function getHtmlScriptRootContainers(): string {
    $html = '';

    foreach ( $this->scripts as $rootId => $s ) {
      $html .= '<div id="' . $rootId . '"></div>';
    }

    return $html;
  }


  /** @return array<Script> */
  public function scripts(): array {
    return $this->scripts;
  }


  /**
   * @throws Exception
   * @return static
   */
  public function addScript( Script $script ) {
    if ( array_key_exists( $script->id(), $this->scripts ) ) {
      throw new Exception(
        'Script with id "' . $script->id() . '" already exists.'
      );
    }

    $this->scripts[$script->id()] = $script;
    return $this;
  }


  /** @return array<Style> */
  public function styles(): array {
    return $this->styles;
  }


  /**
   * @throws Exception
   * @return static
   */
  public function addStyle( Style $style ) {
    if ( array_key_exists( $style->id(), $this->styles ) ) {
      throw new Exception(
        'Style with id "' . $style->id() . '" already exists.'
      );
    }

    $this->styles[$style->id()] = $style;
    return $this;
  }


  /** @return array<Endpoint> */
  public function endpoints() {
    return $this->endpoints;

  }


  /** @return static */
  public function addEndpoint( Endpoint $endpoint ) {
    $this->endpoints[] = $endpoint;
    return $this;
  }


  /**
   * @param array<Endpoint> $endpoints
   *
   * @return static
   */
  public function setEndpoints( $endpoints ) {
    $this->endpoints = $endpoints;
    return $this;
  }


}
