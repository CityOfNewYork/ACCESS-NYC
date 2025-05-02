<?php

namespace WPML\Legacy\Component\Translation\Domain\Links;

use WPML\Core\Component\Translation\Domain\Links\CollectorInterface;
use WPML\Core\Component\Translation\Domain\Links\Item;
use WPML\Core\Component\Translation\Domain\Links\RepositoryInterface;
use WPML\PHP\Exception\InvalidTypeException;

class Collector implements CollectorInterface {

  /** @var RepositoryInterface */
  private $repostiory;

  /** @var Item[] */
  private $items = [];


  public function __construct( RepositoryInterface $repository ) {
    $this->repostiory = $repository;
  }


  /** @return Item[] */
  public function getItemsLinkedInContent( string $content ) {
    $this->items = [];
    if ( class_exists( '\AbsoluteLinks' ) ) {
        $absoluteLinks = new \AbsoluteLinks();
        $absoluteLinks->convert_text( $content, false, $this );
    }

    return $this->items;
  }


  /**
   * This method is called by legacy AbsoluteLinks::convert_text().
   *
   * @return void
   */
  public function addItemByIdAndType( int $id, string $type ) {
    try {
      if ( ! in_array( $id, array_column( $this->items, 'id' ) ) ) {
        $this->items[] = $this->repostiory->get( $id, $type );
      }
    } catch ( InvalidTypeException $e ) {
      return;
    }
  }


}
