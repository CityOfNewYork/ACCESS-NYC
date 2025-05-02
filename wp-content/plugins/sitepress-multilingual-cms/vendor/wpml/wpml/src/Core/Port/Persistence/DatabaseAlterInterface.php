<?php

namespace WPML\Core\Port\Persistence;

use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\PHP\Exception\InvalidArgumentException;

interface DatabaseAlterInterface {


  /**
   * @param string          $table
   * @param string|string[] $fields
   * @parame string|null    $name   Optional. If not provided the first field name is used.
   *
   * @return bool
   *
   * @throws DatabaseErrorException
   * @throws InvalidArgumentException
   */
  public function addIndex( string $table, $fields, string $name = null );


}
