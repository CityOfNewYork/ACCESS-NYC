<?php

// phpcs:ignore PHPCompatibility.Keywords.ForbiddenNamesAsDeclared.stringFound
namespace WPML\Core\Component\Translation\Application\String\Repository;

use WPML\Core\Component\Translation\Application\String\StringException;

interface StringBatchRepositoryInterface {


  /**
   * @param string $name
   * @param int[]  $stringIds
   *
   * @return int
   * @throws StringException
   *
   */
  public function create( string $name, array $stringIds, string $sourceLanguageCode ): int;


}
