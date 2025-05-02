<?php

namespace WPML\Infrastructure\WordPress\Port\Persistence;

use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;


class DatabaseWrite implements \WPML\Core\Port\Persistence\DatabaseWriteInterface {

  /** @var \wpdb $wpdb */
  private $wpdb;


  /**
   * @param \wpdb $wpdb Type defined here to allow injecting the global.
   */
  public function __construct( $wpdb ) {
    $this->wpdb = $wpdb;
  }


  /**
   * @param string               $table
   * @param array<string, mixed> $entityData
   *
   * @return int
   * @throws DatabaseErrorException
   *
   */
  public function insert( string $table, array $entityData ): int {
    $table = $this->wpdb->prefix . $table;

    $this->wpdb->insert( $table, $entityData );

    if ( $this->wpdb->last_error ) {
      throw new DatabaseErrorException( $this->wpdb->last_error );
    }

    return $this->wpdb->insert_id;
  }


  /**
   * @param string                 $table
   * @param array<string, mixed>[] $entitiesData
   *
   * @return void
   * @throws DatabaseErrorException
   */
  public function insertMany( string $table, array $entitiesData ) {
    $table = $this->wpdb->prefix . $table;

    $fields = implode( ', ', array_keys( $entitiesData[0] ) );
    $values = $this->prepareValues( $entitiesData );

    // use insert ignore into with many rows
    $sql = sprintf(
      "INSERT IGNORE INTO $table ( %s ) VALUES %s",
      $fields,
      $values
    );

    $this->wpdb->query( $sql );

    if ( $this->wpdb->last_error ) {
      throw new DatabaseErrorException( $this->wpdb->last_error );
    }
  }


  /**
   * @param array<string, mixed>[] $entitiesData
   *
   * @return string
   */
  private function prepareValues( array $entitiesData ): string {
    $values = array_map(
      function ( $entityData ) {
        return '(' . implode(
          ', ',
          array_map(
            function ( $value ) {
              /** @phpstan-ignore-next-line */
              return $this->wpdb->_real_escape( $value );
            },
            $entityData
          )
        ) . ')';
      },
      $entitiesData
    );

    return implode( ', ', $values );
  }


  /**
   * @param string               $table
   * @param array<string, mixed> $entityData
   * @param array<string, mixed> $whereData
   *
   * @return int
   * @throws DatabaseErrorException
   */
  public function update( string $table, array $entityData, array $whereData ): int {
    $this->wpdb->update( $this->wpdb->prefix . $table, $entityData, $whereData );

    if ( $this->wpdb->last_error ) {
      throw new DatabaseErrorException( $this->wpdb->last_error );
    }

    return $this->wpdb->rows_affected;
  }


}
