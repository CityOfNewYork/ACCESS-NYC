<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\Events\Item\WordCount;

use WPML\Core\Component\Post\Application\WordCount\ItemWordCountService;
use WPML\Core\Port\Event\EventListenerInterface;
use WPML\PHP\Exception\InvalidItemIdException;
use function WPML\PHP\Logger\notice;

class CalculateWordsInPostListener implements EventListenerInterface {

  /** @var ItemWordCountService */
  private $itemWordCountService;


  public function __construct( ItemWordCountService $itemWordCountService ) {
    $this->itemWordCountService = $itemWordCountService;
  }


  /**
   * @param int|mixed $currentValue
   * @param int|mixed $postId
   *
   * @return int|mixed
   */
  public function calculate( $currentValue, $postId ) {
    if ( ! $currentValue && is_numeric( $postId ) ) {
      try {
        $currentValue = $this->itemWordCountService->calculatePost( (int) $postId );
      } catch ( InvalidItemIdException $e ) {
        notice( sprintf( 'Invalid post ID %d', $postId ) );
      }
    }

    return $currentValue;
  }


}
