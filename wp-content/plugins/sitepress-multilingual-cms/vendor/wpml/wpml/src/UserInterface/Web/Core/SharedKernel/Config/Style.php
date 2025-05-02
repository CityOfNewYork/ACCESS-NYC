<?php

namespace WPML\UserInterface\Web\Core\SharedKernel\Config;

class Style {

  /** @var string $id */
  private $id;

  /** @var ?string $src */
  private $src;

  /** @var array<string> $dependencies **/
  private $dependencies = [];


  public function __construct( string $id ) {
    $this->id = $id;
  }


  public function id(): string {
    return $this->id;
  }


  /** @return ?string */
  public function src() {
    return $this->src;
  }


  /** @return static */
  public function setSrc( string $src ) {
    $this->src = $src;
    return $this;
  }


  /**
   * @return array<string>
   */
  public function dependencies(): array {
    return $this->dependencies;
  }


  /**
   * @param array<string> $dependencies
   * @return static
   */
  public function setDependencies( $dependencies ) {
    $this->dependencies = $dependencies;
    return $this;
  }


}
