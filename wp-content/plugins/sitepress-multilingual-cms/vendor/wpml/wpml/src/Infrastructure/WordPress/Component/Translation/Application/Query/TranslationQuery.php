<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Query;

use WPML\Core\Component\Translation\Application\Query\TranslationQueryInterface;
use WPML\Core\Component\Translation\Domain\Translation;
use WPML\Core\Component\Translation\Domain\TranslationType;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;
use WPML\PHP\Exception\InvalidArgumentException;

/**
 * @phpstan-type TranslationRow array{
 *    job_id: int|null,
 *    automatic: int|null,
 *    editor: string|null,
 *    job_completed: int|null,
 *    status: int,
 *    batch_id: int|null,
 *    translation_service: string|null,
 *    translator_id: int|null,
 *    translation_id: int,
 *    review_status?: string|null,
 *    source_language_code: string,
 *    language_code: string,
 *    element_type: string,
 *    translated_element_id: int|null,
 *    original_element_id: int,
 *    needs_update: int
 *  }
 *
 */
class TranslationQuery implements TranslationQueryInterface {

  /** @phpstan-var QueryHandlerInterface<int, TranslationRow> $queryHandler */
  private $queryHandler;

  /** @var QueryPrepareInterface $queryPrepare */
  private $queryPrepare;

  /** @var TranslationResultMapper $resultMapper */
  private $resultMapper;


  /**
   * @phpstan-param QueryHandlerInterface<int, TranslationRow> $queryHandler
   * @param QueryPrepareInterface $queryPrepare
   * @param TranslationResultMapper $resultMapper
   */
  public function __construct(
    QueryHandlerInterface $queryHandler,
    QueryPrepareInterface $queryPrepare,
    TranslationResultMapper $resultMapper
  ) {
    $this->queryHandler = $queryHandler;
    $this->queryPrepare = $queryPrepare;
    $this->resultMapper = $resultMapper;
  }


  public function getManyByJobIds( array $jobIds ): array {
    if ( empty( $jobIds ) ) {
      return [];
    }

    $ids   = $this->queryPrepare->prepareIn( $jobIds, '%d' );
    $query = $this->getBasicQuery() . " WHERE job.job_id IN ($ids) LIMIT " . count( $jobIds );

    try {
      $rows = $this->queryHandler->query( $query );

      return $this->mapResult( $rows->getResults() );
    } catch ( DatabaseErrorException $e ) {
      return [];
    } catch ( InvalidArgumentException $e ) {
      return [];
    }
  }


  public function getOneByJobId( int $jobId ) {
    $query = $this->getBasicQuery() . " WHERE job.job_id = %d LIMIT 1";

    try {
      $row = $this->queryHandler->queryOne(
        $this->queryPrepare->prepare( $query, $jobId )
      );
      if ( ! is_array( $row ) ) {
        return null;
      }

      return $this->resultMapper->mapRow( $row );
    } catch ( DatabaseErrorException $e ) {
      return null;
    } catch ( InvalidArgumentException $e ) {
      return null;
    }
  }


  public function getManyByTranslatedElementIds( array $translatedElementIds ): array {
    if ( empty( $translatedElementIds ) ) {
      return [];
    }

    $ids   = $this->queryPrepare->prepareIn( $translatedElementIds, '%d' );
    $query = $this->getBasicQuery() . " WHERE translation.element_id IN ($ids) LIMIT " . count( $translatedElementIds );

    try {
      $rows = $this->queryHandler->query( $query );

      return $this->mapResult( $rows->getResults() );
    } catch ( DatabaseErrorException $e ) {
      return [];
    } catch ( InvalidArgumentException $e ) {
      return [];
    }
  }


  public function getManyByElementIds(
    TranslationType $translationType,
    array $elementIds
  ): array {
    if ( empty( $elementIds ) ) {
      return [];
    }

    $ids   = implode( ',', $elementIds );

    $type  = $this->queryPrepare->prepare(
      " AND translation.element_type LIKE %s",
      $translationType->get() . '_%'
    );

    $tridIn = " WHERE original.trid IN (
    SELECT trid from `{$this->queryPrepare->prefix()}icl_translations`
    WHERE element_id IN ($ids)
    )";

    $sourceLanguageNotNull = " AND translation.source_language_code IS NOT NULL";

    $query = $this->getBasicQuery() . $tridIn . $type . $sourceLanguageNotNull;

    try {
      $rows = $this->queryHandler->query( $query );

      return $this->mapResult( $rows->getResults() );
    } catch ( DatabaseErrorException $e ) {
      return [];
    } catch ( InvalidArgumentException $e ) {
      return [];
    }
  }


  /**
   * @phpstan-param TranslationRow[] $rowset
   *
   * @return Translation[]
   */
  private function mapResult( array $rowset ): array {
    return array_map(
      [
        $this->resultMapper,
        'mapRow'
      ],
      $rowset
    );
  }


  private function getBasicQuery(): string {
    return "
      SELECT
        `job`.`job_id`,
        `job`.`automatic`,
        `job`.`editor`,
        `job`.`translated` AS `job_completed`,
        `status`.`status`,
        `status`.`batch_id`,
        `status`.`translation_service`,
        `status`.`translator_id`,
        `status`.`review_status`,
        `status`.`needs_update`,
        `translation`.`translation_id`,
        `translation`.`source_language_code`,
        `translation`.`language_code`,
        `translation`.`element_type`,
        `translation`.`element_id` AS `translated_element_id`,
        `original`.`element_id` AS `original_element_id`
      FROM `{$this->queryPrepare->prefix()}icl_translations` AS `translation`
      INNER JOIN `{$this->queryPrepare->prefix()}icl_translations` AS `original` 
      ON `original`.`trid` = `translation`.`trid` AND `original`.`source_language_code` IS NULL
      INNER JOIN `{$this->queryPrepare->prefix()}icl_translation_status` AS `status` 
      ON `status`.`translation_id` = `translation`.`translation_id`
      LEFT JOIN `{$this->queryPrepare->prefix()}icl_translate_job` AS `job` ON `job`.`job_id` = (
        SELECT MAX(`job_id`) 
        FROM `{$this->queryPrepare->prefix()}icl_translate_job` 
        WHERE `rid` = `status`.`rid`
      )
    ";
  }


}
