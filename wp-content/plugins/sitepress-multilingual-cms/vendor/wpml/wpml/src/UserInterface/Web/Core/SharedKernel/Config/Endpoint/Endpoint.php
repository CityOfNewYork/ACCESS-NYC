<?php

namespace WPML\UserInterface\Web\Core\SharedKernel\Config\Endpoint;

class Endpoint {
  const NAMESPACE = 'wpml';

  /** @var string $id */
  private $id;

  /** @var string $path */
  private $path;

  /** @var MethodType::* $method */
  private $method;

  /** @var class-string|null $handler ; */
  private $handler;

  /** @var ?string $capability */
  private $capability;

  /** @var int $version */
  private $version = 1;

  /** @var bool $isAjax */
  private $isAjax;


  public function __construct( string $id, string $path, bool $isAjax = false ) {
    $this->id         = $id;
    $this->path       = $path;
    $this->method     = MethodType::GET;
    $this->isAjax     = $isAjax;
  }


  public function id(): string {
    return $this->id;
  }


  public function path(): string {
    return $this->path;
  }


  public function isAjax(): bool {
    return $this->isAjax;
  }


  public function namespaceWithVersion(): string {
    return self::NAMESPACE . '/v' . $this->version();
  }


  public function route(): string {
    return $this->namespaceWithVersion() . $this->path();
  }


  /** @return MethodType::* */
  public function method() {
    return $this->method;
  }


  /**
   *
   * @param MethodType::* $method
   *
   * @return static
   */
  public function setMethod( $method ) {
    $this->method = $method;

    return $this;
  }


  /** @return class-string|null */
  public function handler() {
    return $this->handler;
  }


  /**
   * @param class-string $handler
   * @return static
   */
  public function setHandler( $handler ) {
    $this->handler = $handler;

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


  public function version(): int {
    return $this->version;
  }


  /** @return static */
  public function setVersion( int $version ) {
    $this->version = $version;

    return $this;
  }


}
