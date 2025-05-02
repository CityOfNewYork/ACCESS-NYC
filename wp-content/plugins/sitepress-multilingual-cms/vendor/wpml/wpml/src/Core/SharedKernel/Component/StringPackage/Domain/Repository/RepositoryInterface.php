<?php

namespace WPML\Core\SharedKernel\Component\StringPackage\Domain\Repository;

use WPML\PHP\Exception\InvalidArgumentException;

interface RepositoryInterface {


  /**
   * @param int        $packageId
   * @param string     $field
   * @param int|string $value
   *
   * @return void
   * @throws InvalidArgumentException
   */
  public function updateField( int $packageId, string $field, $value );


}
