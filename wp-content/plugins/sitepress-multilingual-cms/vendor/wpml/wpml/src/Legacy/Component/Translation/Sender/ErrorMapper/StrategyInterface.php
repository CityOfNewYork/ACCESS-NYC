<?php

namespace WPML\Legacy\Component\Translation\Sender\ErrorMapper;

interface StrategyInterface {


  /**
   * @param array{id?: string, type?: string, text?: string}[] $errors
   *
   * @return string|null
   */
  public function map( array $errors );


}
