<?php

namespace WPML\Core\Component\Translation\Domain\Links;

use WPML\PHP\Exception\InvalidTypeException;

interface RepositoryInterface {


  /** @throws InvalidTypeException */
  public function get( int $id, string $type ): Item;


  /** @return Item[] */
  public function getToItemsByFromItem( Item $item );


  /** @return Item[] */
  public function getFromItemsByToItem( Item $item );


  /** @return void */
  public function addRelationship( Item $from, Item $to );


  /** @return void */
  public function deleteRelationship( Item $from, Item $to );


  /** @return void */
  public function deleteAllRelationshipsFrom( Item $item );


  /** @return void */
  public function deleteAllRelationshipsTo( Item $item );


}
