<?php

namespace WPML\Core\SharedKernel\Component\Translation\Domain;

class ReviewStatus {

  const NEEDS_REVIEW = 'NEEDS_REVIEW';
  const EDITING = 'EDITING';
  const ACCEPTED = 'ACCEPTED';

  /** @var self::* */
  private $value;


  public function __construct( string $value ) {
    if ( ! self::isAllowed( $value ) ) {
      $value = self::NEEDS_REVIEW;
    }

    /** @var self::* $value */
    $this->value = $value;
  }


  /** @return self::* */
  public function getValue() {
    return $this->value;
  }


  private static function isAllowed( string $value ): bool {
    return in_array(
      $value,
      [ self::NEEDS_REVIEW, self::EDITING, self::ACCEPTED ],
      true
    );
  }


}
