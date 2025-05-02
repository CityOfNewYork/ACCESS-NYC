<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Query;

use WPML\Core\Component\Translation\Application\Query\ItemLanguageQueryInterface;
use WPML\Core\Component\Translation\Domain\TranslationType;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;
use WPML\PHP\Exception\InvalidArgumentException;


/**
 * @phpstan-type ItemLanguageRow array{
 *   itemId: int,
 *   type: 'post'|'package'|'st-batch',
 *   language: string
 * }
 */
class ItemLanguageQuery implements ItemLanguageQueryInterface {

  /** @phpstan-var  QueryHandlerInterface<int, ItemLanguageRow> $queryHandler */
  private $queryHandler;

  /** @var QueryPrepareInterface $queryPrepare */
  private $queryPrepare;


  /**
   * @phpstan-param  QueryHandlerInterface<int, ItemLanguageRow> $queryHandler
   * @param QueryPrepareInterface $queryPrepare
   */
  public function __construct(
    QueryHandlerInterface $queryHandler,
    QueryPrepareInterface $queryPrepare
  ) {
    $this->queryHandler = $queryHandler;
    $this->queryPrepare = $queryPrepare;
  }


  public function getManyOriginalLanguagesOfItems( array $items ): array {
    if ( ! $items ) {
      return [];
    }

    $where = implode(
      ' OR ',
      array_map(
        function ( $item ) {
          $type = $item['type']->get() === TranslationType::STRING_BATCH ?
            'st-batch' :
            $item['type']->get();

          return $this->queryPrepare->prepare(
            "( element_id = %d AND element_type LIKE %s )",
            $item['itemId'],
            $type . '_%'
          );
        },
        $items
      )
    );

    $sql = "
      SELECT
        element_id as itemId,
        SUBSTRING_INDEX(element_type, '_', 1) as type,
        (
          SELECT language_code
          FROM {$this->queryPrepare->prefix()}icl_translations original_element
          WHERE original_element.trid = current_element.trid 
            AND original_element.source_language_code IS NULL           
        ) as language
      FROM {$this->queryPrepare->prefix()}icl_translations current_element      
      WHERE {$where}
    ";

    try {
      $rowset = $this->queryHandler->query( $sql )->getResults();
    } catch ( DatabaseErrorException $e ) {
      $rowset = [];
    }

    return array_map(
      function ( $row ) {
        try {
          $type = $row['type'] === 'st-batch' ? TranslationType::STRING_BATCH : $row['type'];

          $type = new TranslationType( $type );
        } catch ( InvalidArgumentException $e ) {
          $type = TranslationType::post();
        }

        return [
          'itemId'   => $row['itemId'],
          'type'     => $type,
          'language' => $row['language'],
        ];
      },
      $rowset
    );
  }


}
