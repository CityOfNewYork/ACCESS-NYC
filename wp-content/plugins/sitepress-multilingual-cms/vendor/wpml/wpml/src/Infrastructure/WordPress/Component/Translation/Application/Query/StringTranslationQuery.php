<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Query;

use WPML\Core\Component\Translation\Domain\Translation;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;
use WPML\PHP\Exception\InvalidArgumentException;

/**
 * @phpstan-import-type TranslationRow from TranslationQuery
 *
 */
class StringTranslationQuery {

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


  /**
   * @param int[] $stringIds
   *
   * @return Translation[]
   */
  public function getStringTranslations( array $stringIds ): array {
    if ( empty( $stringIds ) ) {
      return [];
    }

    $ids = implode( ',', array_map( 'intval', $stringIds ) );
    $query = $this->getBasicQuery() . "WHERE s.id IN ($ids)";

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
    /*
     * Fields status, batch_id, translation_service and translator_id exists in 2 tables:
     * icl_string_translations and icl_translation_status.
     *
     * Which ones we should use for strings?
     * 1) status = icl_string_translations table. Because icl_translation_status table
     * stores status per strings batch and not per individual strings.
     *
     * 1 string batch = N strings selected for translation for 1 language per current selection.
     * So, 5 selected strings for translation to 2 languages will create 2 batches(1 per language).
     *
     * 2) batch_id, translation_service, translator_id = icl_translation_status table.
     * I tested our current automatic translation for strings and those fields are not filled
     * at all when we translate strings automatically from ST dashboard. So, we should use
     * columns from icl_translation_status table instead.
     */
    return "
      SELECT
        `job`.`job_id`,
        `job`.`automatic`,
        `job`.`editor`,
        `job`.`translated` AS `job_completed`,
        `st`.`status`,
        `status`.`batch_id`,
        `status`.`translation_service`,
        `status`.`translator_id`,
        `status`.needs_update,
        `st`.`id` AS `translation_id`,
        `s`.`language` AS `source_language_code`,
        `st`.`language` AS `language_code`,
        'st-batch' AS `element_type`,
        `st`.`id` AS `translated_element_id`,
        `s`.`id` AS `original_element_id`
      FROM `{$this->queryPrepare->prefix()}icl_string_translations` AS `st`
      INNER JOIN `{$this->queryPrepare->prefix()}icl_strings` AS `s` 
      ON `st`.`string_id` = `s`.`id`
      LEFT JOIN `{$this->queryPrepare->prefix()}icl_string_batches` AS `batch`
      ON `batch`.`string_id` = `s`.`id`
      LEFT JOIN `{$this->queryPrepare->prefix()}icl_translations` AS `translation`
      ON `translation`.`element_id` = `batch`.`batch_id` AND `translation`.`element_type` = 'st-batch'
      LEFT JOIN `{$this->queryPrepare->prefix()}icl_translation_status` AS `status` 
      ON `status`.`translation_id` = `translation`.`translation_id`
      LEFT JOIN `{$this->queryPrepare->prefix()}icl_translate_job` AS `job` ON `job`.`rid` = `status`.`rid`
    ";
  }


}
