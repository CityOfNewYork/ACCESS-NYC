<?php

namespace WPML\UserInterface\Web\Core\SharedKernel\Domain;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Script {

  /** @var string $id */
  private $id;

  /** @var string $src */
  private $src;

  /** @var array<string> $dependencies **/
  private $dependencies;


  /**
   * @param string $id
   * @param string $src
   * @param array<string> $dependencies
   */
  public function __construct(
        string $id,
        string $src,
        array $dependencies = []
    ) {
    $this->id = $id;
    $this->src = $src;
    $this->dependencies = $dependencies;
  }


  public function id(): string {
    return $this->id;
  }


  public function src(): string {
    return $this->src;
  }


  /**
   * @return array<string> $dependencies
   */
  public function dependencies(): array {
    return $this->dependencies;
  }


  /**
   * @param array<string> $dependencies
   * @return static
   */
  public function setDependencies( array $dependencies ) {
    $this->dependencies = $dependencies;
    return $this;
  }


}
