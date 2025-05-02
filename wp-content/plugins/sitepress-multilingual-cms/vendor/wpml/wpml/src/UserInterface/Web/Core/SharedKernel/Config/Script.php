<?php

namespace WPML\UserInterface\Web\Core\SharedKernel\Config;

use WPML\PHP\Exception\InvalidArgumentException;

class Script {
  const USED_ON_ADMIN = 'admin';
  const USED_ON_FRONT = 'front';
  const USED_ON_BOTH = 'both';

  /** @var string $id */
  private $id;

  /** @var ?string $src */
  private $src;

  /** @var array<string> $dependencies **/
  private $dependencies = [];

  /** @var ?class-string $dataProvider */
  private $dataProvider;

  /** @var ?class-string $prerequisites */
  private $prerequisites;

  /** @var bool $onlyRegister */
  private $onlyRegister = false;

  /** @var string $usedOn */
  private $usedOn = self::USED_ON_ADMIN;

  /** @var ?string $scriptVarName */
  private $scriptVarName;

  /** @var array<string> $scriptData */
  private $scriptData = [];


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


  /** @return array<string> */
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


  /**
   * @return ?class-string
   */
  public function dataProvider() {
    return $this->dataProvider ?? null;
  }


  /**
   * @param class-string $dataProvider
   * @return static
   */
  public function setDataProvider( $dataProvider ) {
    $this->dataProvider = $dataProvider;
    return $this;
  }


  /**
   * @return ?class-string
   */
  public function prerequisites() {
    return $this->prerequisites ?? null;
  }


  /**
   * @param class-string $prerequisites
   * @return static
   */
  public function setPrerequisites( $prerequisites ) {
    $this->prerequisites = $prerequisites;
    return $this;
  }


  public function onlyRegister(): bool {
    return $this->onlyRegister;
  }


  /** @return static */
  public function setOnlyRegister( bool $onlyRegister ) {
    $this->onlyRegister = $onlyRegister;
    return $this;
  }


  public function usedOnAdmin(): bool {
    return $this->usedOn === self::USED_ON_ADMIN
      || $this->usedOn === self::USED_ON_BOTH;
  }


  public function usedOnFront(): bool {
    return $this->usedOn === self::USED_ON_FRONT
      || $this->usedOn === self::USED_ON_BOTH;
  }


  /**
   * @throws InvalidArgumentException
   * @return static
   */
  public function setUsedOn( string $usedOn ) {
    $valid = [ self::USED_ON_ADMIN, self::USED_ON_FRONT, self::USED_ON_BOTH ];
    if ( ! in_array( $usedOn, $valid ) ) {
      throw new InvalidArgumentException(
        'Invalid value "'. $usedOn .'" for usedOn. ' .
        'Valid options: '. implode( ' | ', $valid ) . '.'
      );
    }
    $this->usedOn = $usedOn;
    return $this;
  }


  /** @return ?string */
  public function scriptVarName() {
    return $this->scriptVarName ?? $this->id;
  }


  /**
   * @param string $scriptVarName
   * @return static
   */
  public function setScriptVarName( string $scriptVarName ): self {
    $this->scriptVarName = $scriptVarName;
    return $this;
  }


  /**
   * @return array<string>
   */
  public function scriptData(): array {
    return $this->scriptData;
  }


  /**
   * @param array<string>  $scriptData
   * @return static
   */
  public function setScriptData( array $scriptData ): self {
    $this->scriptData = $scriptData;
    return $this;
  }


}
