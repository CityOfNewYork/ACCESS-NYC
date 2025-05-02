<?php

namespace WPML\PHP;

use WPML\PHP\Exception\InvalidArgumentException;

class DateTime extends \DateTime {


  /**
   * @param string $datetime
   * @param \DateTimeZone|null $timezone
   *
   * @throws InvalidArgumentException
   */
  public function __construct( $datetime = 'now', \DateTimeZone $timezone = null ) {
    try {
      parent::__construct( $datetime, $timezone );
    } catch ( \Throwable $e ) {
      throw new InvalidArgumentException( $e->getMessage() );
    }
  }


  /**
   * @param string|null $datetime
   * @param \DateTimeZone|null $timezone
   *
   * @return DateTime|null
   */
  public static function create( $datetime = 'now', \DateTimeZone $timezone = null ) {
    if ( ! $datetime ) {
      return null;
    }

    try {
      return new DateTime( $datetime, $timezone );
    } catch ( InvalidArgumentException $e ) {
      return null;
    }
  }


}
