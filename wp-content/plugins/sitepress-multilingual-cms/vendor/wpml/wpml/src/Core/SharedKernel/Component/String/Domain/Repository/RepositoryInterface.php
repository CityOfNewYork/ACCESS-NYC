<?php

// phpcs:ignore PHPCompatibility.Keywords.ForbiddenNamesAsDeclared.stringFound
namespace WPML\Core\SharedKernel\Component\String\Domain\Repository;

use WPML\Core\SharedKernel\Component\String\Domain\StringEntity;
use WPML\PHP\Exception\InvalidArgumentException;
use WPML\PHP\Exception\InvalidItemIdException;

interface RepositoryInterface {


  /**
   * @param int $stringId
   *
   * @return StringEntity
   * @throws InvalidItemIdException
   */
  public function get( int $stringId ): StringEntity;


  /**
   * @param int $packageId
   *
   * @return StringEntity[]
   */
  public function getBelongingToPackage( int $packageId ): array;


  /**
   * @param int        $stringId
   * @param string     $field
   * @param int|string $value
   *
   * @return void
   * @throws InvalidArgumentException
   */
  public function updateField( int $stringId, string $field, $value );


}
