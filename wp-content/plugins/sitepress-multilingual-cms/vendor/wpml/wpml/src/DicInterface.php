<?php

namespace WPML;

interface DicInterface {


  /**
   * @template T
   *
   * @param class-string<T>                   $classname
   * @param array<string>|array<string,mixed> $args
   *
   * Auryn returns 'mixed' so we need to ignore the type checking here.
   *
   * @psalm-suppress MixedInferredReturnType
   * @psalm-suppress MixedReturnStatement
   *
   * @return T
   */
  public function make( $classname, $args = [] );


  /**
   * Takes a classname or an object to pass it on further usages as singleton.
   *
   * @param string|object $classnameOrObject
   *
   * @return void
   */
  public function share( $classnameOrObject );


  /**
   * Define instantiation directives for the specified class
   *
   * @param string               $name The class to define arguments for.
   * @param array<string, mixed> $args An array mapping parameter names to values.
   *
   * @return void
   */
  public function define( $name, array $args );


  /**
   * Allows to define a global param.
   *
   * @param string $name
   * @param mixed  $value
   *
   * @return void
   */
  public function defineParam( $name, $value );


  /**
   * Defines which implementation should be used for an interface type hint.
   *
   * @param string $interfaceName
   * @param string $implementationName
   *
   * @return void
   */
  public function alias( $interfaceName, $implementationName );


  /**
   * @param string $name
   * @param callable $factory
   *
   * @return void
   */
  public function delegate( $name, $factory );


}
