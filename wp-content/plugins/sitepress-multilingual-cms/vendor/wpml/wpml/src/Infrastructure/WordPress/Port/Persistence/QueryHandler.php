<?php

namespace WPML\Infrastructure\WordPress\Port\Persistence;

use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\ResultCollection;
use WPML\Core\Port\Persistence\ResultCollectionInterface;
use tad\FunctionMocker\ReturnValue;

/**
 * QueryHandler provides only read access to the database.
 *
 * @template ReturnKey as array-key
 * @template ReturnValue
 * @implements QueryHandlerInterface<ReturnKey, ReturnValue>
 */
class QueryHandler implements QueryHandlerInterface {

  /** @var \wpdb $wpdb */
  private $wpdb;


  /**
   * @param \wpdb $wpdb Type defined here to allow injecting the global.
   */
  public function __construct( $wpdb ) {
    $this->wpdb = $wpdb;
  }


  /**
   * @param string $query
   *
   * @return ResultCollectionInterface<ReturnKey, ReturnValue>
   * @throws DatabaseErrorException
   *
   */
  public function query( string $query ) {
    /** @var array<ReturnKey, ReturnValue> $data */
    $data = $this->wpdb->get_results( $query, ARRAY_A );

    if ( $this->wpdb->last_error ) {
      throw new DatabaseErrorException( $this->wpdb->last_error );
    }

    return new ResultCollection( $data );
  }


  public function queryOne( string $query ) {

    /** @var ReturnValue|null $data */
    $data = $this->wpdb->get_row( $query, ARRAY_A );

    if ( $this->wpdb->last_error ) {
      throw new DatabaseErrorException( $this->wpdb->last_error );
    }

    return $data;
  }


  /**
   * @param string $query
   *
   * @throws DatabaseErrorException
   *
   * @return ReturnValue|null
   */
  public function querySingle( string $query ) {
    /** @var ReturnValue|null $value */
    $value = $this->wpdb->get_var( $query );

    if ( $this->wpdb->last_error ) {
      throw new DatabaseErrorException( $this->wpdb->last_error );
    }

    return $value;
  }


  public function queryColumn( string $query ) {
    $result = $this->wpdb->get_col( $query );

    if ( $this->wpdb->last_error ) {
      throw new DatabaseErrorException( $this->wpdb->last_error );
    }

    return $result;
  }


}
