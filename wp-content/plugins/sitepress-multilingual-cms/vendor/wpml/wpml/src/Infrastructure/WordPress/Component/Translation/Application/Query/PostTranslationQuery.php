<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Query;

use WPML\Core\Component\Translation\Application\Query\PostTranslationQueryInterface;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;

class PostTranslationQuery implements PostTranslationQueryInterface {

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


  public function getOriginalPostId( int $translatedPostId ): int {
    $sql = "
      SELECT element_id
      FROM {$this->queryPrepare->prefix()}icl_translations
      WHERE trid = (
        SELECT trid
        FROM {$this->queryPrepare->prefix()}icl_translations
        WHERE element_id = %d AND element_type LIKE 'post_post'
        LIMIT 1
      ) AND source_language_code IS NULL
    ";

    $sql = $this->queryPrepare->prepare( $sql, $translatedPostId );

    try {
      $originalPostId = (int) $this->queryHandler->querySingle( $sql );
    } catch ( DatabaseErrorException $e ) {
      $originalPostId = 0;
    }

    // If $originalPostId is zero, it means that the post is also the original post.
    // It means that the post is not translated.
    return $originalPostId ?: $translatedPostId;
  }


}
