<?php

// phpcs:ignore PHPCompatibility.Keywords.ForbiddenNamesAsDeclared.stringFound
namespace WPML\Infrastructure\WordPress\Component\Translation\Application\String\Query;

use WPML\Core\Component\Translation\Application\String\Query\StringsFromBatchQueryInterface;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;

class StringsFromBatchQuery implements StringsFromBatchQueryInterface {

  /** @var QueryHandlerInterface<int, int> $queryHandler */
  private $queryHandler;

  /** @var QueryPrepareInterface $queryPrepare */
  private $queryPrepare;


  /**
   * @param QueryHandlerInterface<int, int> $queryHandler
   * @param QueryPrepareInterface $queryPrepare
   */
  public function __construct(
    QueryHandlerInterface $queryHandler,
    QueryPrepareInterface $queryPrepare
  ) {
    $this->queryHandler = $queryHandler;
    $this->queryPrepare = $queryPrepare;
  }


  public function get( int $batchId ): array {
    $sql = "
      SELECT b.string_id
      FROM {$this->queryPrepare->prefix()}icl_string_batches b
      WHERE b.batch_id = %d
    ";

    $sql = $this->queryPrepare->prepare( $sql, $batchId );

    try {
      return $this->queryHandler->queryColumn( $sql );
    } catch ( DatabaseErrorException $e ) {
      return [];
    }
  }


}
