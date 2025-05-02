<?php

namespace WPML\Legacy\Component\Post\Application\Query;

use WPML\Core\Component\Post\Application\Query\Criteria\HierarchicalPostCriteria;
use WPML\Core\Component\Post\Application\Query\Dto\HierarchicalPostDto;
use WPML\Core\Component\Post\Application\Query\HierarchicalPostQueryInterface;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;

/**
 * @phpstan-type HierarchicalPostItem array{
 *   ID: int,
 *   post_title: string,
 *   post_parent: int
 * }
 */
class HierarchicalPostQuery implements HierarchicalPostQueryInterface {

  /** @var QueryPrepareInterface */
  private $queryPrepare;

  /** @var QueryHandlerInterface<int, HierarchicalPostItem> */
  private $queryHandler;


  /**
   * @param QueryPrepareInterface $queryPrepare
   * @param QueryHandlerInterface<int, HierarchicalPostItem> $queryHandler
   */
  public function __construct(
    QueryPrepareInterface $queryPrepare,
    QueryHandlerInterface $queryHandler
  ) {
    $this->queryPrepare = $queryPrepare;
    $this->queryHandler = $queryHandler;
  }


  /**
   * @param HierarchicalPostCriteria $criteria
   * @return HierarchicalPostDto[]
   * @throws \WPML\Core\Port\Persistence\Exception\DatabaseErrorException
   */
  public function getMany( HierarchicalPostCriteria $criteria ) {
    // Query - get all posts type "$criteria->getType()" only if they are present in another row as ``post_parent``, limit by "$criteria->getLimit()" and offset by "$criteria->getOffset()"
    $query = $this->prepareQuery( $criteria );

    $wpPages = $this->queryHandler->query( $query )->getResults();

    if ( empty( $wpPages ) ) {
      return [];
    }

    return array_map(
      function( $wpPage ) {
        return new HierarchicalPostDto(
          $wpPage['ID'],
          // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          $wpPage['post_title'],
          // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          $wpPage['post_parent']
        );
      },
      $wpPages
    );
  }


  /**
   * @param HierarchicalPostCriteria $criteria
   *
   * @return string
   */
  private function prepareQuery( HierarchicalPostCriteria $criteria ) {
    $prefix = $this->queryPrepare->prefix();
    $query = "
      SELECT
        DISTINCT wpp.ID,
        wpp.post_title,
        wpp.post_parent
      FROM
        {$prefix}posts as wpp
      INNER JOIN {$prefix}posts AS wparent
        ON wparent.post_parent = wpp.ID
      INNER JOIN {$prefix}icl_translations AS wtr
        ON wtr.element_id = wpp.ID
      WHERE
        wpp.post_type = %s
        AND wparent.post_type = %s
        AND wtr.language_code = %s
        AND wtr.element_type = %s
    ";

    $element_type = sprintf( 'post_%s', $criteria->getType() );

    $basePart = $this->queryPrepare->prepare(
      $query,
      $criteria->getType(),
      $criteria->getType(),
      $criteria->getSourceLanguageCode(),
      $element_type
    );

    $searchPart = $criteria->getSearch() ?
      $this->queryPrepare->prepare( 'AND wpp.post_title LIKE %s', '%' . $criteria->getSearch() . '%' ) : '';

    $limitPart = $criteria->getLimit() > 0 ?
      $this->queryPrepare->prepare( 'LIMIT %d', $criteria->getLimit() ) : '';

    $offsetPart = $criteria->getOffset() > 0 ?
      $this->queryPrepare->prepare( 'OFFSET %d', $criteria->getOffset() ) : '';

    return $basePart . $searchPart . $limitPart . $offsetPart;
  }


}
