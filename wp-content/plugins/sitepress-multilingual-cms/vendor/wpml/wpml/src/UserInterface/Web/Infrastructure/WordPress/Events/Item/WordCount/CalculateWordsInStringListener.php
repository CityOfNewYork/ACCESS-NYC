<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\Events\Item\WordCount;

use WPML\Core\Component\Post\Application\WordCount\ItemWordCountService;
use WPML\Core\Port\Event\EventListenerInterface;
use WPML\PHP\Exception\InvalidItemIdException;
use function WPML\PHP\Logger\notice;

class CalculateWordsInStringListener implements EventListenerInterface {

  /** @var ItemWordCountService */
  private $itemWordCountService;


  public function __construct( ItemWordCountService $itemWordCountService ) {
    $this->itemWordCountService = $itemWordCountService;
  }


  /**
   * @param int|mixed $currentValue
   * @param int|mixed $stringId
   *
   * @return int|mixed
   */
  public function calculate( $currentValue, $stringId ) {
    if ( ! $currentValue && is_numeric( $stringId ) ) {
      try {
        $currentValue = $this->itemWordCountService->calculateString( (int) $stringId );
      } catch ( InvalidItemIdException $e ) {
        notice( sprintf( 'Invalid string ID %d', $stringId ) );
      }
    }

    return $currentValue;
  }


}
