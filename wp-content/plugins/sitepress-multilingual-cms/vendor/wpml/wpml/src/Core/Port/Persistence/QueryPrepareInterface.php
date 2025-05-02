<?php

namespace WPML\Core\Port\Persistence;

interface QueryPrepareInterface {


  public function prefix(): string;


  /**
   * @param string $sql
   * @param array<scalar>|scalar $args
   * @return string
   */
  public function prepare( $sql, ...$args ): string;


  /**
   * @param array<scalar>|scalar $items
   * @param string $format
   * @return string
   */
  public function prepareIn( $items, $format = '%s' ): string;


  /**
   * @param string|null $text
   * @return string
   */
  public function escLike( $text ): string;


  /**
   * @param string|string[] $text
   * @psalm-return ($text is string ? string : string[])
   */
  public function escString( $text );


}
