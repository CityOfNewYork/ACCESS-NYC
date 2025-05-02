<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\Events\Item\WordCount;

use WPML\Core\Component\Post\Domain\WordCount\Calculator\Calculator;
use WPML\Core\Port\Event\EventListenerInterface;

class CountListener implements EventListenerInterface {

  /** @var Calculator */
  private $calculator;


  public function __construct( Calculator $calculator ) {
    $this->calculator = $calculator;
  }


  public function onCalculateChars( string $content ): int {
    return $this->calculator->chars( $content );
  }


  public function onCalculateWords( string $content ): int {
    return $this->calculator->words( $content );
  }


}
