<?php

namespace WPML\Core\Port\Persistence;

use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;

/**
 * QueryHandlerInterface provides only read access to the database.
 *
 * @template ReturnKey as array-key
 * @template ReturnValue
 */
interface QueryHandlerInterface {


  /**
   * Performs a query and returns a collection of results.
   *
   * @return ResultCollectionInterface<ReturnKey, ReturnValue>
   * @throws DatabaseErrorException
   *
   */
  public function query( string $query );


  /**
   * @return ReturnValue|null
   * @throws DatabaseErrorException
   *
   */
  public function queryOne( string $query );


  /**
   * Performs a query and returns a single result.
   *
   * @return ReturnValue|null
   * @throws DatabaseErrorException
   *
   */
  public function querySingle( string $query );


  /**
   * Performs a query and returns a single column.
   *
   * @return array<ReturnValue>
   * @throws DatabaseErrorException
   *
   */
  public function queryColumn( string $query );


}
