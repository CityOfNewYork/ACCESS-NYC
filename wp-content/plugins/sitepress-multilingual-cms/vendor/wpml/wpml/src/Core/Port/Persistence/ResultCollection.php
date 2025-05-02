<?php

namespace WPML\Core\Port\Persistence;

use WPML\Core\Port\Persistence\Exception\CanOnlyHydrateFromArrayException;
use WPML\Core\Port\Persistence\Exception\EmptyQueryResultException;
use WPML\Core\Port\Persistence\Exception\NotConstructableFromArrayException;
use WPML\Core\Port\Persistence\Exception\NotUniqueQueryResultException;
use WPML\PHP\ConstructableFromArrayInterface;

/**
 * @template ItemKey as array-key
 * @template ItemValue
 *
 * @implements ResultCollectionInterface<ItemKey, ItemValue>
 */
class ResultCollection implements ResultCollectionInterface {

  /**
   * @var array<ItemKey, ItemValue>
   */
  private $itemList;


  /**
   * @param array<ItemKey, ItemValue> $itemList
   */
  public function __construct( array $itemList = [] ) {
    $this->itemList = $itemList;
  }


  /**
   * @throws EmptyQueryResultException If the query returned no results.
   * @throws NotUniqueQueryResultException If the query returned more than one result.
   *
   * @return ItemValue
   */
  public function getSingleResult() {
    if ( ! $this->itemList ) {
      throw new EmptyQueryResultException();
    }

    $count = $this->count();

    if ( $count > 1 ) {
      throw new NotUniqueQueryResultException();
    }

    return \reset( $this->itemList );
  }


  /** @return array<ItemKey, ItemValue> */
  public function getResults() {
    return $this->itemList;
  }


  public function count(): int {
    return \count( $this->itemList );
  }


  /**
   * @template T of ConstructableFromArrayInterface
   * @param class-string<T> $classname
   *
   * @throws CanOnlyHydrateFromArrayException If an item is not an array.
   *
   * @return ResultCollectionInterface<ItemKey, T>
   */
  public function hydrateResultItemsAs(
    string $classname
  ): ResultCollectionInterface {
    if (
      ! is_subclass_of( $classname, ConstructableFromArrayInterface::class )
    ) {
      throw new NotConstructableFromArrayException( $classname );
    }

    $hydratedItemList = [];
    foreach ( $this->itemList as $itemKey => $item ) {
      if ( ! \is_array( $item ) ) {
        throw new CanOnlyHydrateFromArrayException( $item );
      }

      $itemValue = $classname::fromArray( $item );

      if ( $itemValue instanceof $classname ) {
        // This check is necessary to let PHPStan know the type.
        $hydratedItemList[ $itemKey ] = $itemValue;
      }
    }

    return new self( $hydratedItemList );
  }


  /**
   * @template T
   * @param class-string<T> $classname
   *
   * @throws NotConstructableFromArrayException If the given class does not implement ConstructableFromArrayInterface.
   * @throws CanOnlyHydrateFromArrayException If the result is not an array.
   * @throws EmptyQueryResultException If the query returned no results.
   * @throws NotUniqueQueryResultException If the query returned more than one result.
   *
   * @return T
   */
  public function hydrateSingleResultAs( string $classname ) {
    if (
      ! is_subclass_of( $classname, ConstructableFromArrayInterface::class )
    ) {
      throw new NotConstructableFromArrayException( $classname );
    }

    $item = $this->getSingleResult();

    if ( ! \is_array( $item ) ) {
      throw new CanOnlyHydrateFromArrayException( $item );
    }

    return $classname::fromArray( $item );
  }


}
