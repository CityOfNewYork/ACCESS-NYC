<?php

namespace WPML\Legacy\Component\Translation\Sender\ErrorMapper;

class ErrorMapper {

  /** @var StrategyInterface[] */
  private $strategies;


  /**
   * @param StrategyInterface[] $strategies
   */
  public function __construct( array $strategies ) {
    $this->strategies = $strategies;
  }


  /**
   * @param array{id?: string, type?: string, text?: string}[] $errors
   *
   * @return string
   */
  public function map( array $errors ): string {
    foreach ( $this->strategies as $strategy ) {
      $message = $strategy->map( $errors );
      if ( $message ) {
        return $message;
      }
    }

    return __( 'The jobs could not be created.', 'wpml' );
  }


}
