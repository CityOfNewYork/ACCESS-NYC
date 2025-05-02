<?php

namespace WPML\Infrastructure\WordPress\Component\TranslationProxy\Application\Query;

use WPML\Core\Component\TranslationProxy\Application\Query\RemoteJobsQueryInterface;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationStatus;

/**
 * @phpstan-type RemoteJobsCount int
 */
class RemoteJobsQuery implements RemoteJobsQueryInterface {

  /** @phpstan-var QueryHandlerInterface<int, RemoteJobsCount> $queryHandler */
  private $queryHandler;

  /** @var QueryPrepareInterface */
  private $queryPrepare;


  /**
   * @param QueryHandlerInterface<int, RemoteJobsCount> $queryHandler
   * @param QueryPrepareInterface $queryPrepare
   */
  public function __construct(
    QueryHandlerInterface $queryHandler,
    QueryPrepareInterface $queryPrepare
  ) {
    $this->queryHandler = $queryHandler;
    $this->queryPrepare = $queryPrepare;
  }


  public function getCount( int $currentTranslationServiceId ): int {
    $statuses = implode(
      ',',
      [
        TranslationStatus::IN_PROGRESS,
        TranslationStatus::READY_TO_DOWNLOAD
      ]
    );

    $query = "SELECT COUNT(tstatus.rid) AS remote_jobs_count
    FROM {$this->queryPrepare->prefix()}icl_translation_status AS tstatus
    INNER JOIN {$this->queryPrepare->prefix()}icl_translations AS translations
    ON translations.translation_id = tstatus.translation_id
    INNER JOIN {$this->queryPrepare->prefix()}icl_translations as original_translations
    ON original_translations.trid = translations.trid
    AND original_translations.language_code = translations.source_language_code
    WHERE tstatus.translation_service = {$currentTranslationServiceId}
    AND tstatus.status IN ({$statuses})
    ";

    try {
      $count = intval( $this->queryHandler->querySingle( $query ) );
    } catch ( DatabaseErrorException $e ) {
      $count = 0;
    }

    return $count;
  }


}
