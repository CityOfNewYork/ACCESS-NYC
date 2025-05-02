<?php

namespace WPML\Core\SharedKernel\Component\Translator\Domain\Query;

use WPML\Core\SharedKernel\Component\Translator\Domain\Translator;

interface TranslatorsQueryInterface {


  /**
   * @return Translator[]
   */
  public function get();


  /**
   * @param int $id
   *
   * @return Translator|null
   */
  public function getById( int $id );


  /**
   * @return Translator|null
   */
  public function getCurrentlyLoggedId();


}
