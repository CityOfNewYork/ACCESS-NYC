<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchCriteria;
use WPML\Core\Component\Post\Application\Query\Dto\PostWithTranslationStatusDto;
use WPML\Core\Component\Post\Application\Query\Dto\TranslationStatusDto;
use WPML\Core\Port\Persistence\ResultCollection;
use WPML\Core\Port\Persistence\ResultCollectionInterface;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationEditorType;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationMethod\TargetLanguageMethodType;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationStatus;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery;
use WPML\PHP\Exception\InvalidArgumentException;
use function WPML\PHP\array_keys_exists;


/**
 * @phpstan-import-type TargetLanguageMethodTypeValues from TargetLanguageMethodType
 * @phpstan-import-type PostsData from SearchQuery
 * @phpstan-import-type SearchQueryJobData from TranslationsQuery
 */
class ItemWithTranslationStatusDtoMapper {

  const REQUIRED_RAW_KEYS = [
    'ID',
    'post_title',
    'post_status',
    'post_date',
    'post_type',
    'word_count',
    'translator_note'
  ];


  /**
   * @param ResultCollectionInterface<int,PostsData>          $items
   * @param ResultCollectionInterface<int,SearchQueryJobData> $jobs
   * @param SearchCriteria                                    $searchCriteria
   *
   * @psalm-suppress ArgumentTypeCoercion Validation on mapSingle().
   *
   * @return ResultCollectionInterface<int,PostWithTranslationStatusDto>
   * @throws InvalidArgumentException
   */
  public function mapCollection(
    ResultCollectionInterface $items,
    ResultCollectionInterface $jobs,
    SearchCriteria $searchCriteria
  ) {
    $mappedItems = [];

    $postJobs = [];
    foreach ( $jobs->getResults() as $job ) {
      $postJobs[ $job['original_element_id'] ][] = $job;
    }

    foreach ( $items->getResults() as $rawData ) {
      $mappedItems[] = $this->mapSingle( $rawData, $postJobs[ $rawData['ID'] ] ?? [], $searchCriteria );
    }

    return new ResultCollection( $mappedItems );
  }


  /**
   * @param PostsData                      $rawData
   * @param array<int, SearchQueryJobData> $jobs
   * @param SearchCriteria                 $searchCriteria
   *
   * @return PostWithTranslationStatusDto
   * @throws InvalidArgumentException One of the required keys is missing.
   *
   */
  private function mapSingle( array $rawData, array $jobs, SearchCriteria $searchCriteria ) {
    if ( ! array_keys_exists( self::REQUIRED_RAW_KEYS, $rawData ) ) {
      throw new InvalidArgumentException(
        'Invalid raw data for ItemWithTranslationStatusDtoMapper.' .
        'Required keys: ' . implode( ', ', self::REQUIRED_RAW_KEYS )
      );
    }

    $translationStatuses = $this->mapTranslationStatuses( $jobs, $searchCriteria );

    return new PostWithTranslationStatusDto(
      (int) $rawData['ID'],
      $rawData['post_title'],
      $rawData['post_status'],
      $rawData['post_date'],
      $rawData['post_type'],
      $translationStatuses,
      is_numeric( $rawData['word_count'] ) ? (int) $rawData['word_count'] : null,
      $rawData['translator_note']
    );
  }


  /**
   * @param array<int, SearchQueryJobData> $jobs
   * @param SearchCriteria                 $searchCriteria
   *
   * @return array<string, TranslationStatusDto>
   */
  private function mapTranslationStatuses( array $jobs, SearchCriteria $searchCriteria ): array {
    $translationStatuses = [];

    // First, create default TranslationStatusDto for all target languages
    foreach ( $searchCriteria->getTargetLanguageCodes() as $langCode ) {
      $translationStatuses[ $langCode ] = new TranslationStatusDto( 0 );
    }

    // Then override with actual translation statuses from jobs
    foreach ( $jobs as $job ) {
      $langCode = $job['language_code'];

      $status = TranslationStatus::getPostDisplayStatus( $job )->get();

      $isTranslated = (int) $job['element_id'] > 0;
      $editor       = $this->parseEditor( $job['editor'] );
      $method       = $this->getMethod(
        $status,
        $job['translation_service'] ?: 'NULL',
        (int) $job['automatic'] > 0,
        (int) $job['job_id']
      );

      $translationStatuses[ $langCode ] = new TranslationStatusDto(
        $status,
        $job['review_status'],
        $job['job_id'] ? (int) $job['job_id'] : null,
        $method,
        $editor,
        $isTranslated,
        $job['translator_id'] ? (int) $job['translator_id'] : null
      );
    }

    return $translationStatuses;
  }


  /**
   * @param string|null $editor
   *
   * @return TranslationEditorType::*
   */
  private function parseEditor( string $editor = null ) {
    if ( ! $editor || $editor === 'NULL' ) {
      return TranslationEditorType::NONE;
    }

    if ( $editor === 'wp' ) {
      return TranslationEditorType::WORDPRESS;
    }

    if ( $editor === 'wpml' ) {
      return TranslationEditorType::CLASSIC;
    }

    if ( in_array( $editor, TranslationEditorType::getTypes(), true ) ) {
      return $editor;
    }

    return TranslationEditorType::NONE;
  }


  /**
   * @param int    $status
   * @param string $translationService
   * @param bool   $automatic
   * @param int    $jobId
   *
   * @return TargetLanguageMethodTypeValues|null
   */
  private function getMethod( int $status, string $translationService, bool $automatic, int $jobId ) {
    $method = null;
    if ( $status === TranslationStatus::DUPLICATE ) {
      $method = TargetLanguageMethodType::DUPLICATE;
    } else if ( $translationService !== 'local' && $translationService !== '' && $translationService !== 'NULL' ) {
      $method = TargetLanguageMethodType::TRANSLATION_SERVICE;
    } else if ( $automatic ) {
      $method = TargetLanguageMethodType::AUTOMATIC;
    } else if ( $jobId ) {
      $method = TargetLanguageMethodType::LOCAL_TRANSLATOR;
    }

    return $method;
  }


}
