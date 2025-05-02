<?php

namespace WPML\Core\Component\Translation\Domain\Settings;

class ReviewMode {

  const REVIEW_BEFORE_PUBLISH = 'before-publish';
  const PUBLISH_AND_REVIEW = 'publish-and-review';
  const PUBLISH_WITHOUT_REVIEW = 'no-review';

  /** @var string */
  private $value;


  public function __construct( string $value ) {
    $this->value = self::isAllowed( $value ) ? $value : self::REVIEW_BEFORE_PUBLISH;
  }


  public function getValue(): string {
    return $this->value;
  }


  private static function isAllowed( string $value ): bool {
    return in_array(
      $value,
      [ self::REVIEW_BEFORE_PUBLISH, self::PUBLISH_AND_REVIEW, self::PUBLISH_WITHOUT_REVIEW ],
      true
    );
  }


  public static function createDefault(): self {
    return new self( self::REVIEW_BEFORE_PUBLISH );
  }


}
