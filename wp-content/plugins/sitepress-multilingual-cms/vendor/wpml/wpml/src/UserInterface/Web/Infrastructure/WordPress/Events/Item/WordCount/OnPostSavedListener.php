<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\Events\Item\WordCount;

use WPML\Core\Component\Post\Application\WordCount\ItemWordCountService;
use WPML\Core\Port\Event\EventListenerInterface;
use WPML\PHP\Exception\InvalidItemIdException;

class OnPostSavedListener implements EventListenerInterface {

  /** @var ItemWordCountService */
  private $itemWordCountService;

  /** @var int[] */
  private $postIdsToProcess = [];


  public function __construct( ItemWordCountService $itemWordCountService ) {
    $this->itemWordCountService = $itemWordCountService;
  }


  /**
   * @param int $postId
   * @param \WP_Post $post
   *
   * @return void
   */
  public function onPostSaved( int $postId, $post ) {
    $excludeStatuses = [ 'auto-draft', 'trash', 'inherit' ];

    // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    if ( ! in_array( $post->post_status, $excludeStatuses, true ) ) {
      $this->postIdsToProcess[] = $postId;
    }
  }


  /**
   * @return void
   */
  public function process() {
    $this->postIdsToProcess = array_unique( $this->postIdsToProcess );

    foreach ( $this->postIdsToProcess as $postId ) {
      // @phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
      try {
        $this->itemWordCountService->calculatePost( $postId, true );
      } catch ( InvalidItemIdException $e ) {
        // Do nothing. Apparently, the post has been removed in the meantime, so we can ignore it.
        // It usually happens inside phpunit integration tests.
      }
      // @phpcs:enable
    }

    $this->postIdsToProcess = [];
  }


}
