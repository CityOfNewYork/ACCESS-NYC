<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery;

use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;
use WPML\Core\Port\Persistence\ResultCollection;
use WPML\Core\Port\Persistence\ResultCollectionInterface;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery;

/**
 * @phpstan-import-type PostsData from SearchQuery
 *
 * @phpstan-type SearchQueryJobData array{
 *  language_code:string,
 *  element_id:int|string,
 *  trid:int|string,
 *  original_element_id:int|string,
 *  status:int|string,
 *  review_status:'ACCEPTED'|'EDITING'|'NEEDS_REVIEW'|null,
 *  needs_update:int,
 *  rid:int|string,
 *  job_id:string,
 *  translator_id:string,
 *  automatic:string,
 *  translation_service:string,
 *  editor:string
 *  }
 */
class TranslationsQuery {

  /** @var QueryPrepareInterface */
  private $queryPrepare;

  /** @var QueryHandlerInterface<int, array<string,mixed>> */
  private $queryHandler;


  /**
   * @param QueryPrepareInterface                           $queryPrepare
   * @param QueryHandlerInterface<int, array<string,mixed>> $queryHandler
   */
  public function __construct(
    QueryPrepareInterface $queryPrepare,
    QueryHandlerInterface $queryHandler
  ) {
    $this->queryPrepare = $queryPrepare;
    $this->queryHandler = $queryHandler;
  }


  /**
   * @param ResultCollectionInterface<int,PostsData> $posts
   * @param string                                 $postType
   * @param string                                 $sourceLanguageCode
   *
   * @return ResultCollectionInterface<int,SearchQueryJobData>
   * @throws DatabaseErrorException
   */
  public function get(
    ResultCollectionInterface $posts,
    string $postType,
    string $sourceLanguageCode
  ): ResultCollectionInterface {
    $query = $this->buildQuery( $posts, $postType, $sourceLanguageCode );
    if ( ! $query ) {
      return new ResultCollection( [] );
    }

    /** @var ResultCollectionInterface<int,SearchQueryJobData> */
    return $this->queryHandler->query( $query );
  }


  /**
   * @param ResultCollectionInterface<int,PostsData> $posts
   * @param string                                 $postType
   * @param string                                 $sourceLanguageCode
   *
   * @return string|null
   */
  private function buildQuery(
    ResultCollectionInterface $posts,
    string $postType,
    string $sourceLanguageCode
  ) {
    $postIds = [];

    foreach ( $posts->getResults() as $post ) {
      $postIds[] = (int) $post['ID'];
    }

    if ( empty( $postIds ) ) {
      return null;
    }

    $gluedPostIds = implode( ',', $postIds );

    $sql = "
      SELECT
        target_t.language_code,
        target_t.element_id,
        target_t.trid,
        source_t.element_id as original_element_id,
        ts.status,
        ts.review_status,
        ts.needs_update,
        tj.rid,
        tj.job_id,
        tj.translator_id,
        tj.automatic,
        ts.translation_service,
        tj.editor
      FROM {$this->queryPrepare->prefix()}icl_translations source_t

      INNER JOIN {$this->queryPrepare->prefix()}icl_translations target_t
        ON target_t.trid = source_t.trid

      LEFT JOIN {$this->queryPrepare->prefix()}icl_translation_status ts
            ON ts.translation_id = target_t.translation_id

      LEFT JOIN {$this->queryPrepare->prefix()}icl_translate_job tj
        ON tj.job_id = (
            SELECT MAX(job_id)
            FROM {$this->queryPrepare->prefix()}icl_translate_job
            WHERE rid = ts.rid
        )

      WHERE target_t.language_code != %s
        AND source_t.element_type = %s
        AND source_t.element_id IN ($gluedPostIds)
    ";

    return $this->queryPrepare->prepare( $sql, $sourceLanguageCode, 'post_' . $postType );
  }


}
