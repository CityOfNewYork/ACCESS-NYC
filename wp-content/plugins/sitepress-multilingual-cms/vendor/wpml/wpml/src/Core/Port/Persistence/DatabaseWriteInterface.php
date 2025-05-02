<?php

namespace WPML\Core\Port\Persistence;

use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;

interface DatabaseWriteInterface {


  /**
   * @param string               $table
   * @param array<string, mixed> $entityData
   *
   * @return int
   * @throws DatabaseErrorException
   *
   */
  public function insert( string $table, array $entityData ): int;


  /**
   * @param string                 $table
   * @param array<string, mixed>[] $entitiesData
   *
   * @return void
   * @throws DatabaseErrorException
   */
  public function insertMany( string $table, array $entitiesData );


  /**
   * @param string $table
   * @param array<string, mixed>  $entityData
   * @param array<string, mixed>  $whereData
   *
   * @return int
   * @throws DatabaseErrorException
   */
  public function update( string $table, array $entityData, array $whereData ): int;


}
