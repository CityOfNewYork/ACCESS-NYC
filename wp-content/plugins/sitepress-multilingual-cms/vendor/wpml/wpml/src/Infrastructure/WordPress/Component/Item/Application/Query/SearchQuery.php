<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchCriteria;
use WPML\Core\Component\Post\Application\Query\SearchQueryInterface;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\ResultCollectionInterface;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\ItemWithTranslationStatusDtoMapper;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\QueryBuilderResolver;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\TranslationsQuery;
use WPML\PHP\Exception\InvalidArgumentException;

/**
 * @phpstan-type PostsData array{
 *    ID:int|string,
 *    post_title:string,
 *    post_status:string,
 *    post_date:string,
 *    post_type:string,
 *    word_count:string,
 *    translator_note:string
 * }
 */
class SearchQuery implements SearchQueryInterface {

  /** @var QueryBuilderResolver */
  private $queryBuilderResolver;

  /** @var QueryHandlerInterface<int, array<string,mixed>> */
  private $queryHandler;

  /** @var ItemWithTranslationStatusDtoMapper */
  private $mapper;

  /** @var TranslationsQuery */
  private $translationsQuery;


  /**
   * @param QueryBuilderResolver                            $queryBuilderResolver
   * @param QueryHandlerInterface<int, array<string,mixed>> $queryHandler
   * @param ItemWithTranslationStatusDtoMapper              $mapper
   * @param TranslationsQuery                               $translationsQuery
   */
  public function __construct(
    QueryBuilderResolver $queryBuilderResolver,
    QueryHandlerInterface $queryHandler,
    ItemWithTranslationStatusDtoMapper $mapper,
    TranslationsQuery $translationsQuery
  ) {
    $this->queryBuilderResolver = $queryBuilderResolver;
    $this->queryHandler         = $queryHandler;
    $this->mapper               = $mapper;
    $this->translationsQuery    = $translationsQuery;
  }


  /**
   * @throws DatabaseErrorException
   * @throws InvalidArgumentException
   */
  public function get( SearchCriteria $criteria ) {
    $query = $this->queryBuilderResolver->resolveSearchQueryBuilder()->build( $criteria );

    /** @var ResultCollectionInterface<int, PostsData> $posts */
    $posts = $this->queryHandler->query( $query );

    $jobs = $this->translationsQuery->get( $posts, $criteria->getType(), $criteria->getSourceLanguageCode() );

    return $this->mapper->mapCollection( $posts, $jobs, $criteria );
  }


  /**
   * @throws DatabaseErrorException
   */
  public function count( SearchCriteria $criteria ): int {
    $query = $this->queryBuilderResolver->resolveSearchQueryBuilder()->buildCount( $criteria );

    /** @var string $count */
    $count = $this->queryHandler->querySingle( $query );

    return (int) $count;
  }


}
