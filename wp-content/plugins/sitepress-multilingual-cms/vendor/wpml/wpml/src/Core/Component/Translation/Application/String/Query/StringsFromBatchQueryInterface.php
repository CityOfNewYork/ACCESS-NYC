<?php

// phpcs:ignore PHPCompatibility.Keywords.ForbiddenNamesAsDeclared.stringFound
namespace WPML\Core\Component\Translation\Application\String\Query;

interface StringsFromBatchQueryInterface {


  /**
   * Gets string ids belonging to a batch.
   *
   * @param int $batchId
   *
   * @return int[]
   */
  public function get( int $batchId ): array;


}
