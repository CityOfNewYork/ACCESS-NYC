<?php

namespace WPML\Infrastructure\WordPress\CompositionRoot;

use Auryn\Injector;

/**
 * DIC implemmentation using Auryn.
 *
 * @see https://github.com/rdlowrey/Auryn The used dependency injector.
 */
class DIC {

  /** @var Injector */
  private $dic;


  public function __construct() {
    $this->dic = new Injector();
  }


  /**
   * @template T
   *
   * @param class-string<T> $classname
   * @param array<string>|array<string,mixed> $args
   *
   * @throws \Auryn\InjectionException
   *
   * Auryn returns 'mixed' so we need to ignore the type checking here.
   * @psalm-suppress MixedInferredReturnType
   * @psalm-suppress MixedReturnStatement
   *
   * @return T
   */
  public function make( $classname, $args = [] ) {
    return $this->dic->make( $classname, $args );
  }


  /**
   * Takes a classname or an object to pass it on further usages as singleton.
   *
   * @param string|object $classnameOrObject
   *
   * @throws \Auryn\ConfigException
   *
   * @return void
   */
  public function share( $classnameOrObject ) {
    $this->dic->share( $classnameOrObject );
  }


  /**
   * Define instantiation directives for the specified class
   *
   * @param string $name The class to define arguments for.
   * @param array<string, mixed> $args An array mapping parameter names to values.
   *
   * @return void
   */
  public function define( $name, array $args ) {
    $this->dic->define( $name, $args );
  }


  /**
   * Allows to define a global param.
   *
   * @param string $name
   * @param mixed $value
   *
   * @return void
   */
  public function defineParam( $name, $value ) {
    $this->dic->defineParam( $name, $value );
  }


  /**
   * Defines which implementation should be used for an interface type hint.
   *
   * @param string $interfaceName
   * @param string $implementationName
   *
   * @throws \Auryn\ConfigException
   *
   * @return void
   */
  public function alias( $interfaceName, $implementationName ) {
    $this->dic->alias( $interfaceName, $implementationName );
  }


}
