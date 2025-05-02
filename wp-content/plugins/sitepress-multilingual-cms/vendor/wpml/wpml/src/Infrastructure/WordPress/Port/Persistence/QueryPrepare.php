<?php

namespace WPML\Infrastructure\WordPress\Port\Persistence;

use WPML\Core\Port\Persistence\QueryPrepareInterface;

class QueryPrepare implements QueryPrepareInterface {

  /** @var \wpdb $wpdb */
  private $wpdb;


  /**
   * @param \wpdb $wpdb Type defined here to allow injecting the global.
   */
  public function __construct( $wpdb ) {
    $this->wpdb = $wpdb;
  }


  public function prefix(): string {
    return $this->wpdb->prefix;
  }


  /**
   * @param string $sql
   * @param array<scalar>|scalar $args
   * @return string
   */
  public function prepare( $sql, ...$args ): string {
    // @phpstan-ignore-next-line
    $prepared = $this->wpdb->prepare( $sql, $args );
    // Get rid of the possible void return of wpdb::prepare().
    return is_string( $prepared ) ? $prepared : '';
  }


  /**
   * @param array<scalar>|scalar $items
   * @param string $format
   * @return string
   */
  public function prepareIn( $items, $format = '%s' ): string {
    if ( ! is_array( $items ) ) {
      $items = [ $items ];
    }
    $prepared_in = '';
    $itemsCount  = count( $items );

    if ( $itemsCount > 0 ) {
      $placeholders    = array_fill( 0, $itemsCount, $format );
      $prepared_format = implode( ',', $placeholders );
      $prepared_in     = $this->prepare( $prepared_format, ...$items );
    }

    return $prepared_in;
  }


  /**
   * Alias of wpdb::esc_like()
   *
   * @param string|null $text
   * @return string
   */
  public function escLike( $text ): string {
    if ( ! is_null( $text ) && trim( $text ) !== '' ) {
      return $this->wpdb->esc_like( $text );
    }

    return '';
  }


  public function escString( $text ) {
    return esc_sql( $text );
  }


}
