<?php

namespace WPML\Legacy\Component\Translation\Application\Repository;

use WPML\Core\Component\Translation\Application\Repository\TranslationNotFoundException;
use WPML\Core\Component\Translation\Application\Repository\TranslationRepositoryInterface;
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
 *    status: int|null,
 *    batch_id: int|null,
 *    translation_service: string|null,
 *    translator_id: int|null,
 *    translation_id: int,
 *    review_status?: string|null,
 *    source_language_code: string,
 *    language_code: string,
 *    element_type: string,
 *    translated_element_id: int|null,
 *    original_element_id: int|null,
 *    needs_update: int|null
 *  }
 *
 */
class TranslationRepository implements TranslationRepositoryInterface {

  /** @var \SitePress */
  private $sitepress;

  /** @phpstan-var QueryHandlerInterface<int, TranslationRow> $queryHandler */
  private $queryHandler;

  /** @var QueryPrepareInterface $queryPrepare */
  private $queryPrepare;

  /** @var TranslationResultMapper $resultMapper */
  private $resultMapper;


  /**
   * @phpstan-param QueryHandlerInterface<int, TranslationRow> $queryHandler
   *
   * @param QueryPrepareInterface                              $queryPrepare
   * @param \SitePress                                         $sitepress Type only defined here to allow injecting.
   */
  public function __construct(
    QueryHandlerInterface $queryHandler,
    QueryPrepareInterface $queryPrepare,
    TranslationResultMapper $resultMapper,
    $sitepress
  ) {
    $this->queryHandler = $queryHandler;
    $this->queryPrepare = $queryPrepare;
    $this->resultMapper = $resultMapper;
    $this->sitepress    = $sitepress;
  }


  public function get( TranslationType $itemType, string $elementType, int $elementId ): Translation {
    $sql = $this->getQuery();

    try {
      $sql = $this->queryPrepare->prepare( $sql, $elementId, $itemType->get() . '_' . $elementType );

      $row = $this->queryHandler->queryOne( $sql );

      if ( ! is_array( $row ) ) {
        throw new TranslationNotFoundException( $itemType, $elementType, $elementId );
      }

      return $this->resultMapper->mapRow( $row );

    } catch ( DatabaseErrorException $e ) {
      throw new TranslationNotFoundException( $itemType, $elementType, $elementId );
    } catch ( InvalidArgumentException $e ) {
      throw new TranslationNotFoundException( $itemType, $elementType, $elementId );
    }
  }


  /**
   * @param TranslationType $itemType
   * @param string          $elementType
   * @param int             $elementId
   * @param string          $languageCode
   * @param string|null     $sourceLanguageCode
   * @param int|null        $trid
   *
   * @return void
   */
  public function saveElementLanguage(
    TranslationType $itemType,
    string $elementType,
    int $elementId,
    string $languageCode,
    string $sourceLanguageCode = null,
    int $trid = null
  ) {
    $this->sitepress->set_element_language_details(
      $elementId,
      $itemType->get() . '_' . $elementType,
      $trid,
      $languageCode,
      $sourceLanguageCode,
      true
    );
  }


  private function getQuery(): string {
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
      LEFT JOIN `{$this->queryPrepare->prefix()}icl_translations` AS `original` 
      ON `original`.`trid` = `translation`.`trid` AND `original`.`source_language_code` IS NULL
      LEFT JOIN `{$this->queryPrepare->prefix()}icl_translation_status` AS `status` 
      ON `status`.`translation_id` = `translation`.`translation_id`
      LEFT JOIN `{$this->queryPrepare->prefix()}icl_translate_job` AS `job` ON `job`.`job_id` = (
        SELECT MAX(`job_id`) 
        FROM `{$this->queryPrepare->prefix()}icl_translate_job` 
        WHERE `rid` = `status`.`rid`
      )
      
      WHERE `translation`.`element_id` = %d 
        AND `translation`.`element_id` != `original`.`element_id` 
        AND `translation`.`element_type` = %s
    ";
  }


}
