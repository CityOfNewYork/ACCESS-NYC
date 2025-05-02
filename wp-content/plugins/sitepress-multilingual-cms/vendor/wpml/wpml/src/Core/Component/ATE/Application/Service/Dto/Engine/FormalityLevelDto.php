<?php

namespace WPML\Core\Component\ATE\Application\Service\Dto\Engine;

class FormalityLevelDto {
  const LEVEL_MORE = 'more';
  const LEVEL_LESS = 'less';
  const LEVEL_DEFAULT = 'default';

  /** @var 'more'|'less'|'default' */
  private $value;


  public function __construct( string $value ) {
    if ( ! self::isValid( $value ) ) {
      $value = self::LEVEL_DEFAULT;
    }
    /** @var 'more'|'less'|'default' $value */
    $this->value = $value;
  }


  /**
   * @return 'more'|'less'|'default'
   */
  public function getValue(): string {
    return $this->value;
  }


  public static function more(): self {
    return new self( self::LEVEL_MORE );
  }


  public static function less(): self {
    return new self( self::LEVEL_LESS );
  }


  public static function default(): self {
    return new self( self::LEVEL_DEFAULT );
  }


  public static function isValid( string $value ): bool {
    return in_array( $value, [ self::LEVEL_MORE, self::LEVEL_LESS, self::LEVEL_DEFAULT ] );
  }


}
