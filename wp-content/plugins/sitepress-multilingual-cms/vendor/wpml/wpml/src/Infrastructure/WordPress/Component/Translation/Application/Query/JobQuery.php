<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Query;

use WPML\Core\Component\Translation\Application\Query\JobQueryInterface;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;

class JobQuery implements JobQueryInterface {

  /** @phpstan-var QueryHandlerInterface<int, int|bool> $queryHandler */
  private $queryHandler;

  /** @var QueryPrepareInterface $queryPrepare */
  private $queryPrepare;


  /**
   * @phpstan-param QueryHandlerInterface<int, int|bool> $queryHandler
   *
   * @param QueryPrepareInterface                        $queryPrepare
   */
  public function __construct(
    QueryHandlerInterface $queryHandler,
    QueryPrepareInterface $queryPrepare
  ) {
    $this->queryHandler = $queryHandler;
    $this->queryPrepare = $queryPrepare;
  }


  public function hasAnyAutomatic(): bool {
    $query = "
        SELECT EXISTS(
          SELECT 1 
          FROM `{$this->queryPrepare->prefix()}icl_translate_job`
          WHERE automatic = 1
          )
          ";

    return (bool) $this->queryHandler->querySingle( $query );
  }


  public function countAutomaticInProgress(): int {
    $query = "		
          SELECT COUNT(jobs.job_id) 
          FROM {$this->queryPrepare->prefix()}icl_translate_job jobs
          INNER JOIN {$this->queryPrepare->prefix()}icl_translation_status translation_status 
              ON translation_status.rid = jobs.rid
          WHERE automatic = 1 AND translation_status.status IN (1, 2)
          ";

    return (int) $this->queryHandler->querySingle( $query );
  }


}
