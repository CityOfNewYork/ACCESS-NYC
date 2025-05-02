<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\Events\Item\WordCount;

use WPML\Core\Component\Post\Application\WordCount\ItemWordCountService;
use WPML\PHP\Exception\InvalidItemIdException;
use function WPML\PHP\Logger\notice;

class OnStringRegisteredInPackageListener {

  /** @var int[] */
  private $updatedPackages = [];

  /** @var ItemWordCountService */
  private $itemWordCountService;


  public function __construct( ItemWordCountService $itemWordCountService ) {
    $this->itemWordCountService = $itemWordCountService;
  }


  /**
   * @param int $packageId
   *
   * @return void
   */
  public function registerPackage( int $packageId ) {
    $this->updatedPackages[] = $packageId;
  }


  /**
   * @return void
   */
  public function recalculatePackages() {
    foreach ( array_unique( $this->updatedPackages ) as $packageId ) {
      try {
        $this->itemWordCountService->calculatePackage( $packageId );
      } catch ( InvalidItemIdException $e ) {
        notice( sprintf( 'Invalid package ID %d', $packageId ) );
      }
    }
  }


}
