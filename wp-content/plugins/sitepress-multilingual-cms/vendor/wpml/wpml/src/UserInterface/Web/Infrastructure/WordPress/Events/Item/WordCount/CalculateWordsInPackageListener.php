<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\Events\Item\WordCount;

use WPML\Core\Component\Post\Application\WordCount\ItemWordCountService;
use WPML\Core\Port\Event\EventListenerInterface;
use WPML\PHP\Exception\InvalidItemIdException;
use function WPML\PHP\Logger\notice;

class CalculateWordsInPackageListener implements EventListenerInterface {

  /** @var ItemWordCountService */
  private $itemWordCountService;


  public function __construct( ItemWordCountService $itemWordCountService ) {
    $this->itemWordCountService = $itemWordCountService;
  }


  /**
   * @param int|mixed $currentValue
   * @param int|mixed $packageId
   *
   * @return int|mixed
   */
  public function calculate( $currentValue, $packageId ) {
    if ( ! $currentValue && is_numeric( $packageId ) ) {
      try {
        $currentValue = $this->itemWordCountService->calculatePackage( (int) $packageId );
      } catch ( InvalidItemIdException $e ) {
        notice( sprintf( 'Invalid package ID %d', $packageId ) );
      }
    }

    return $currentValue;
  }


}
