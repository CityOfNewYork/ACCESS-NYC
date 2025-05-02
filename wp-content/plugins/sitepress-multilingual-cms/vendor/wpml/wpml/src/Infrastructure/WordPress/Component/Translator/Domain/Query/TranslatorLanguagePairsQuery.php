<?php

namespace WPML\Infrastructure\WordPress\Component\Translator\Domain\Query;

use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;
use WPML\Core\SharedKernel\Component\Translator\Domain\LanguagePair;
use WPML\Core\SharedKernel\Component\Translator\Domain\Query\TranslatorLanguagePairsQueryInterface;

/**
 * @phpstan-type UserMetaRow array{
 *   user_id: int,
 *   meta_value: string,
 * }
 */
class TranslatorLanguagePairsQuery implements TranslatorLanguagePairsQueryInterface {


  const LANGUAGE_PAIRS_META_KEY = 'language_pairs';

  /** @phpstan-var  QueryHandlerInterface<int, UserMetaRow> $queryHandler */
  private $queryHandler;

  /** @var QueryPrepareInterface */
  private $queryPrepare;


  /**
   * @phpstan-param  QueryHandlerInterface<int, UserMetaRow> $queryHandler
   *
   * @param QueryPrepareInterface $queryPrepare
   */
  public function __construct(
    QueryHandlerInterface $queryHandler,
    QueryPrepareInterface $queryPrepare
  ) {
    $this->queryHandler = $queryHandler;
    $this->queryPrepare = $queryPrepare;
  }


  /**
   * @param int $translatorId
   *
   * @return LanguagePair[]
   */
  public function getForSingleTranslator( int $translatorId ): array {
    $metaKey = $this->queryPrepare->prefix() . self::LANGUAGE_PAIRS_META_KEY;

    /**
     * @var false|array<string, array<string, int>> $languagePairs
     */
    $languagePairs = get_user_meta( $translatorId, $metaKey, true );

    $languagePairsDtoArray = [];

    if ( is_array( $languagePairs ) ) {
      foreach ( $languagePairs as $languagePairFrom => $languagePairTo ) {
        $languagePairsDtoArray[] = new LanguagePair(
          $languagePairFrom,
          array_keys( $languagePairTo )
        );
      }
    }

    return $languagePairsDtoArray;
  }


  /**
   * @param int[] $translatorsIds
   *
   * @return array<int, LanguagePair[]>
   */
  public function getForManyTranslators( array $translatorsIds ): array {
    $translatorsIdsIn = implode( ',', $translatorsIds );

    if ( empty( $translatorsIdsIn ) ) {
      return [];
    }

    $sql = "SELECT umeta.user_id, umeta.meta_value 
    FROM {$this->queryPrepare->prefix()}usermeta umeta 
    WHERE umeta.meta_key=%s
    AND umeta.user_id IN($translatorsIdsIn)";

    $preparedSql = $this->queryPrepare->prepare(
      $sql,
      $this->queryPrepare->prefix() . 'language_pairs'
    );

    try {
      /**
       * @var array<array{
       *   user_id: int,
       *   meta_value: string,
       * }> $translatorsLanguagePairsMeta
       */
      $translatorsLanguagePairsMeta = $this->queryHandler->query( $preparedSql )->getResults();
    } catch ( DatabaseErrorException $e ) {
      $translatorsLanguagePairsMeta = [];
    }

    $translatorsIdsWithLanguagePairsArray = [];

    foreach ( $translatorsLanguagePairsMeta as $languagePairsMeta ) {
      /**
       * @var false|array<string, array<string, int>> $languagePairsArray
       */
      $languagePairsArray = unserialize( $languagePairsMeta['meta_value'] );

      if ( ! is_array( $languagePairsArray ) ) {
        continue;
      }

      if ( ! in_array( $languagePairsMeta['user_id'], $translatorsIds ) ) {
        continue;
      }

      foreach ( $languagePairsArray as $languagePairFrom => $languagePairTo ) {
        $translatorsIdsWithLanguagePairsArray[ $languagePairsMeta['user_id'] ][]
          = new LanguagePair( $languagePairFrom, array_keys( $languagePairTo ) );
      }
    }

    return $translatorsIdsWithLanguagePairsArray;
  }


}
