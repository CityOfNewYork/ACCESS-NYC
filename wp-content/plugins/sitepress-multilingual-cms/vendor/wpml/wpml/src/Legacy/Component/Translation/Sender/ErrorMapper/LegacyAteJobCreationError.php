<?php

namespace WPML\Legacy\Component\Translation\Sender\ErrorMapper;

class LegacyAteJobCreationError implements StrategyInterface {


  /**
   * @param array{id?: string, type?: string, text?: string}[] $errors
   *
   * @return string|null
   */
  public function map( array $errors ) {
    foreach ( $errors as $error ) {
      if (
        array_key_exists( 'id', $error )
        && array_key_exists( 'type', $error )
        && array_key_exists( 'text', $error )
        && $error['type'] === 'error'
        && $error['id'] === 'wpml_tm_ate_create_job'
      ) {
        // Use legacy text.
        return $error['text'];
      }
    }

    return null;
  }


}
