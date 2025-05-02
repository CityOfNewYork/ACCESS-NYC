<?php

namespace WPML\Core\Component\Post\Domain\WordCount\Calculator;

use WPML\Core\Component\Post\Domain\WordCount\StripCodeInterface;

class Calculator {

  const AVERAGE_CHARS_PER_WORD = 5;

  /** @var StripCodeInterface */
  private $stripCode;


  public function __construct( StripCodeInterface $stripCode ) {
    $this->stripCode = $stripCode;
  }


  public function chars( string $content ): int {
    $filteredContent = $this->stripCode->strip( $content );

    return strlen( $filteredContent );
  }


  public function words( string $content ): int {
    return (int) round( $this->chars( $content ) / self::AVERAGE_CHARS_PER_WORD );
  }


}
