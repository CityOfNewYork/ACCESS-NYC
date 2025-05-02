<?php

namespace WPML\Core\Component\Translation\Domain\Links;

/**
 * Handles links for translations and also for other translations that link to
 * this translation.
 */
class HandleUpdateTranslation {

  /** @var AdjustLinksInterface */
  private $adjustLinks;

  /** @var RepositoryInterface */
  private $repository;

  /** @var array<string,bool> */
  private $adjustedItems = [];


  public function __construct(
    AdjustLinksInterface $adjustLinks,
    RepositoryInterface $repository
  ) {
    $this->adjustLinks = $adjustLinks;
    $this->repository = $repository;
  }


  /** @return void */
  public function handle( Item $item ) {
    if (
      $item->isOriginal()
      || ! $item->isPublishable()
    ) {
      return;
    }

    if ( $item->canLinkToOtherItems() ) {
      // Adjust the links for the current item.
      $this->adjustOnlyOnce( $item );
    }

    // Adjust the links for other posts that are linked to the current post.
    $this->triggerOtherPostsLinksAdjustment( $item );
  }


  /** @return void */
  private function triggerOtherPostsLinksAdjustment( Item $itemTo ) {
    if (
      ! $itemTo->isPublished()
      || ( ! $itemTo->gotPublished() && ! $itemTo->linkHasChanged() )
    ) {
      // Not published or...
      // published, but neither a new publication nor the link has changed.
      // => No need to adjust the items linking to this time.
      return;
    }

    $itemsFrom = $this->repository->getFromItemsByToItem( $itemTo );

    foreach ( $itemsFrom as $itemFrom ) {
      if ( $itemFrom->isDeleted() ) {
        $this->repository->deleteRelationship( $itemFrom, $itemTo );
        return;
      }

      $this->adjustOnlyOnce( $itemFrom, $itemTo );
    }
  }


  /** @return void */
  private function adjustOnlyOnce( Item $item, Item $triggerItem = null ) {
    $itemIdAndType = $item->getId() . $item->getType();
    if ( array_key_exists( $itemIdAndType, $this->adjustedItems ) ) {
      return;
    }

    $this->adjustLinks->adjust( $item, $triggerItem );
    $this->adjustedItems[ $itemIdAndType ] = true;
  }


}
