<?php

namespace WPML\Core\Component\Translation\Domain\Links;

/**
 * For orginal posts it's required to collect all links to other posts in the
 * content and store them in the database.
 */
class HandleUpdateOriginal {

  /** @var CollectorInterface */
  private $collector;

  /** @var RepositoryInterface */
  private $repository;


  public function __construct(
    CollectorInterface $collector,
    RepositoryInterface $repository
  ) {
    $this->collector = $collector;
    $this->repository = $repository;
  }


  /** @return void */
  public function handle( Item $item ) {
    if (
      ! $item->isOriginal()
      || ! $item->canLinkToOtherItems()
      || ! $item->isPublishable()
    ) {
      return;
    }

    $this->collectRelationships( $item );
  }


  /** @return void */
  private function collectRelationships( Item $item ) {
    $itemContent = $item->getContent() ?? '';
    $itemExcerpt = $item->getExcerpt() ?? '';

    $linksNow = $this->collector->getItemsLinkedInContent(
      $itemContent . $itemExcerpt
    );

    $linksBefore = $this->repository->getToItemsByFromItem(
      $item
    );

    if ( ! $linksNow && ! $linksBefore ) {
      // No links at all.
      return;
    }

    if ( ! $linksNow ) {
      // There are no longer links in the content, but there were some before.
      $this->repository->deleteAllRelationshipsFrom( $item );
      return;
    }

    $diffItems = function( Item $a, Item $b ): int {
      return $a->getId().$a->getType() <=> $b->getId().$b->getType();
    };

    // Delete all links which are no longer in the content.
    $linksToDelete = array_udiff( $linksBefore, $linksNow, $diffItems );

    foreach ( $linksToDelete as $itemTo ) {
      $this->repository->deleteRelationship( $item, $itemTo );
    }

    /** @var Item[] $linksToAdd New links.*/
    $linksToAdd = array_udiff( $linksNow, $linksBefore, $diffItems );
    foreach ( $linksToAdd as $itemTo ) {
      if ( $itemTo->isDeleted() ) {
        // Don't track relationships to deleted items.
        continue;
      }

      $this->repository->addRelationship( $item, $itemTo );
    }
  }


}
