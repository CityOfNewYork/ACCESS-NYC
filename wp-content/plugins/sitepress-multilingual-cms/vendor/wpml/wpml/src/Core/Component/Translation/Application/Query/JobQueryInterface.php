<?php

namespace WPML\Core\Component\Translation\Application\Query;

use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;

interface JobQueryInterface {


  /**
   * It takes into consideration any jobs no matter their status or whether it is the latest job of a translation.
   *
   * @return bool
   * @throws DatabaseErrorException
   */
  public function hasAnyAutomatic(): bool;


  /**
   * @return int
   * @throws DatabaseErrorException
   */
  public function countAutomaticInProgress(): int;


}
