<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Query;

use WPML\Core\Component\Translation\Application\Query\Dto\TranslationBatchDto;
use WPML\Core\Component\Translation\Application\Query\TranslationBatchesQueryInterface;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;
use WPML\Core\SharedKernel\Component\Translation\Domain\ReviewStatus;

/**
 * @phpstan-type TranslationBatchRow array{
 *   id: int,
 *   batch_name: string,
 * }
 */
class TranslationBatchesQuery implements TranslationBatchesQueryInterface {

  /** @phpstan-var QueryHandlerInterface<int, TranslationBatchRow> $queryHandler */
  private $queryHandler;

  /** @var QueryPrepareInterface $queryPrepare */
  private $queryPrepare;


  /**
   * @param QueryHandlerInterface<int, TranslationBatchRow> $queryHandler
   * @param QueryPrepareInterface $queryPrepare
   */
  public function __construct (
    QueryHandlerInterface $queryHandler,
    QueryPrepareInterface $queryPrepare
  ) {
    $this->queryHandler = $queryHandler;
    $this->queryPrepare = $queryPrepare;
  }


  public function getTotalCount (): int {
    $sql = "SELECT COUNT(tb.id) as total_batches_number
    FROM {$this->queryPrepare->prefix()}icl_translation_batches tb";

    try {
      /** @var int|null $totalBatchesNumber */
      $totalBatchesNumber = $this->queryHandler->querySingle( $sql );
    } catch ( DatabaseErrorException $e ) {
      $totalBatchesNumber = 0;
    }

    return intval( $totalBatchesNumber );
  }


  /**
   * @param string $searchName
   *
   * @return TranslationBatchDto[]
   */
  public function getByNameStartsWith ( string $searchName ): array {
    $searchName = preg_replace( '/\s+/', '', $searchName ) ?? $searchName;

    $sql = "SELECT tb.id, tb.batch_name 
    FROM {$this->queryPrepare->prefix()}icl_translation_batches tb
    WHERE REPLACE(REPLACE(REPLACE(tb.batch_name, ' ', ''), '\t', ''), '\n', '') LIKE %s";

    $sqlPrepared = $this->queryPrepare->prepare( $sql, $searchName . '%' );

    try {
      $translationBatches = $this->queryHandler->query( $sqlPrepared )->getResults();
    } catch ( DatabaseErrorException $e ) {
      $translationBatches = [];
    }

    return array_map(
      function ( $translationBatch ) {
        return new TranslationBatchDto(
          $translationBatch[ 'id' ],
          $translationBatch[ 'batch_name' ]
        );
      },
      $translationBatches
    );
  }


  /**
   * @return array<string, bool>
   * - `automatic` => Whether any job exist sent by TEA.
   * - `manual` => Whether any job exist sent manually to automatic translation.
   * @throws DatabaseErrorException
   */
  public function getNeedsReviewJobsBatchType(): array {
    $query = "
      SELECT (SELECT 1
              FROM {$this->queryPrepare->prefix()}icl_translation_status ts
              INNER JOIN {$this->queryPrepare->prefix()}icl_translation_batches tb
                  ON ts.batch_id = tb.id
              WHERE  ts.review_status = '%s'
                  AND ts.status = 10
                  AND tb.batch_name LIKE '%s'
              LIMIT 1 ) AS wpmlTEAJobs,
              (SELECT 1
              FROM {$this->queryPrepare->prefix()}icl_translation_status ts
              INNER JOIN {$this->queryPrepare->prefix()}icl_translation_batches tb
                  ON ts.batch_id = tb.id
              WHERE  ts.review_status = '%s'
              AND ts.status = 10
              AND tb.batch_name NOT LIKE '%s'
              LIMIT 1) AS wpmlAutomaticJobs
        ";
    $preparedQuery = $this->queryPrepare->prepare(
      $query,
      ReviewStatus::NEEDS_REVIEW,
      'Automatic Translations from%',
      ReviewStatus::NEEDS_REVIEW,
      'Automatic Translations from%'
    );

    /** @var array<string, bool> $results */
    $results = $this->queryHandler->queryOne( $preparedQuery );

    return [
      'automatic' => ! empty( $results['wpmlTEAJobs'] ),
      'manual'    => ! empty( $results['wpmlAutomaticJobs'] ),
    ];
  }


}
