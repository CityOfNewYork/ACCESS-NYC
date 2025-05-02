<?php

namespace WPML\Core\Port\Persistence;

use Countable;
use WPML\PHP\ConstructableFromArrayInterface;

/**
 * @template ItemKey
 * @template ItemValue
 */
interface ResultCollectionInterface extends Countable {


  /** @return ItemValue*/
  public function getSingleResult();


  /** @return array<ItemKey, ItemValue> */
  public function getResults();


  public function count(): int;


  /**
   * @template T of ConstructableFromArrayInterface
   * @param class-string<T> $classname
   * @return ResultCollectionInterface<ItemKey, T>
   */
  public function hydrateResultItemsAs( string $classname ): self;


  /**
   * @template T
   * @param class-string<T> $classname
   * @return T
   */
  public function hydrateSingleResultAs( string $classname );


}
