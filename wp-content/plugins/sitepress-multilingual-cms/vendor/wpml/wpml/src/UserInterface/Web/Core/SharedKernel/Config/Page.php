<?php

namespace WPML\UserInterface\Web\Core\SharedKernel\Config;

use WPML\PHP\Exception\Exception;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Endpoint\Endpoint;

class Page {

  /** @var string $id */
  private $id;

  /** @var ?class-string $controllerClassName */
  private $controllerClassName;

  /** @var ?object $controller */
  private $controller;

  /** @var ?string $parentId */
  private $parentId;

  /** @var ?string $legacyParentId */
  private $legacyParentId;

  /** @var ?string $legacyExtension */
  private $legacyExtension;

  /** @var ?string $title */
  private $title;

  /** @var ?string $menuTitle */
  private $menuTitle;

  /** @var ?string $capability */
  private $capability;

  /** @var ?string $icon */
  private $icon;

  /** @var ?int $position */
  private $position;

  /** @var array<Script> $scripts */
  private $scripts = [];

  /** @var array<Style> $styles */
  private $styles = [];

  /** @var array<Endpoint> $endpoints */
  private $endpoints = [];

  /** @var ?class-string $requirementsClassName */
  private $requirementsClassName;


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
    if ( $controller instanceof PageConfigUserInterface ) {
      $controller->setPageConfig( $this );
    }
    $this->controller = $controller;
    return $this;
  }


  /**
   * @return ?string
   */
  public function parentId() {
    return $this->parentId;
  }


  /** @return static */
  public function setParentId( string $parentId ) {
    $this->parentId = $parentId;
    return $this;
  }


  /**
   * @return ?string
   */
  public function legacyParentId() {
    return $this->legacyParentId;
  }


  /** @return static */
  public function setLegacyParentId( string $legacyParentId ) {
    $this->legacyParentId = $legacyParentId;
    return $this;
  }


  /** @return ?string */
  public function legacyExtension() {
    return $this->legacyExtension;
  }


  /** @return static */
  public function setLegacyExtension( string $legacyExtension ) {
    $this->legacyExtension = $legacyExtension;
    return $this;
  }


  public function title(): string {
    return $this->title ?? '';
  }


  /** @return static */
  public function setTitle( string $title ) {
    $this->title = $title;
    return $this;
  }


  public function menuTitle(): string {
    return $this->menuTitle ?? $this->title ?? '';
  }


  /** @return static */
  public function setMenuTitle( string $menuTitle ) {
    $this->menuTitle = $menuTitle;
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
    if ( $this->controller instanceof PageRenderInterface ) {
      $this->controller->render();
      return;
    }

    echo '<div class="wrap">'; // WordPress page wrap.
    echo $this->title ? '<h1>'.$this->title.'</h1>' : '';
    echo $this->getHtmlScriptRootContainers();
    echo '</div>';
  }


  public function getHtmlScriptRootContainers(): string {
    $html = '';
    // remove  wpml-notice-glossary from scripts - we don't want to render it here, it must be rendered on very top of the page
    $this->scripts = array_filter(
      $this->scripts,
      function ( $script ) {
        return $script->id() !== 'wpml-notice-glossary';
      }
    );

    foreach ( $this->scripts as $rootId => $s ) {
      $html .= '<div id="' . $rootId . '"></div>';
    }

    return $html;
  }


  /** @return string */
  public function icon() {
    return $this->icon ?? '';
  }


  /** @return static */
  public function setIcon( string $icon ) {
    $this->icon = $icon;
    return $this;
  }


  /** @return ?int */
  public function position() {
    return $this->position;
  }


  /** @return static */
  public function setPosition( int $position ) {
    $this->position = $position;
    return $this;
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


  /**
   * @return ?class-string
   */
  public function requirementsClassName() {
    return $this->requirementsClassName;
  }


  /**
   * @param class-string $requirementsClassName
   * @return $this
   */
  public function setRequirementsClassName( $requirementsClassName ) {
    $this->requirementsClassName = $requirementsClassName;
    return $this;
  }


}
