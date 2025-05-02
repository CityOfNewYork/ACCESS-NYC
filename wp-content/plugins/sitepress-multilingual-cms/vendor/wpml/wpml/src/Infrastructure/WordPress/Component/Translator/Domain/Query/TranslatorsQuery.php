<?php

namespace WPML\Infrastructure\WordPress\Component\Translator\Domain\Query;

use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;
use WPML\Core\SharedKernel\Component\Translator\Domain\Query\TranslatorLanguagePairsQueryInterface;
use WPML\Core\SharedKernel\Component\Translator\Domain\Query\TranslatorsQueryInterface;
use WPML\Core\SharedKernel\Component\Translator\Domain\Translator;


/**
 * THIS IS CURRENTLY NOT USED.
 * BECAUSE IT ONLY PORTS A PART OF THE LEGACY FUNCTIONALITY AND IS MISSING THE
 * CACHING - WHICH MAKES IT INCREDIBLY SLOW ON SITES WITH THOUSANDS OF USERS.
 *
 * BEFORE USING THIS CLASS, WE NEED TO:
 *  - Cache mechanism - create some generic cache mechanism that can be re-used in other places.
 *  - Legacy must use this new query - we don't want to have two different ways of getting translators.
 *
 * @phpstan-type TranslatorRow array{
 *   ID: int,
 *   display_name: string,
 *   user_nicename: string,
 * }
 */
class TranslatorsQuery implements TranslatorsQueryInterface {

  const CAPABILITY_TRANSLATE = 'translate';

  /** @phpstan-var  QueryHandlerInterface<int, TranslatorRow> $queryHandler */
  private $queryHandler;

  /** @var QueryPrepareInterface */
  private $queryPrepare;

  /** @var TranslatorLanguagePairsQueryInterface */
  private $translatorLanguagePairsQuery;


  /**
   * @phpstan-param  QueryHandlerInterface<int, TranslatorRow> $queryHandler
   *
   * @param QueryPrepareInterface $queryPrepare
   */
  public function __construct(
    QueryHandlerInterface $queryHandler,
    QueryPrepareInterface $queryPrepare,
    TranslatorLanguagePairsQueryInterface $translatorLanguagePairsQuery
  ) {
    $this->queryHandler                 = $queryHandler;
    $this->queryPrepare                 = $queryPrepare;
    $this->translatorLanguagePairsQuery = $translatorLanguagePairsQuery;
  }


  /**
   * @return Translator[]
   */
  public function get() {
    return $this->getTranslators();
  }


  /**
   * @param int $id
   *
   * @return Translator|null
   */
  public function getById( int $id ) {
    $translators = $this->getTranslators( ' AND user.ID=%d', [ $id ] );

    return count( $translators ) ? $translators[0] : null;
  }


  /**
   * @return Translator|null
   */
  public function getCurrentlyLoggedId() {
    $currentUser = \wp_get_current_user();

    if ( $currentUser->ID === 0 ) {
      return null;
    }

    return $this->getById( $currentUser->ID );
  }


  /**
   * @param string $whereClause
   * @param int[]|string[] $whereParams
   *
   * @return Translator[]
   */
  private function getTranslators( string $whereClause = '', array $whereParams = [] ) {
    $sql = "SELECT user.ID, user.display_name, user.user_nicename
      FROM {$this->queryPrepare->prefix()}users user
      INNER JOIN {$this->queryPrepare->prefix()}usermeta umeta
      ON umeta.user_id = user.ID
      AND CAST(umeta.meta_key AS BINARY)=%s
      AND umeta.meta_value LIKE %s" . $whereClause;

    $params = array_merge(
      [
        $this->queryPrepare->prefix() . 'capabilities',
        '%' . self::CAPABILITY_TRANSLATE . '%'
      ],
      $whereParams
    );

    $preparedSql = $this->queryPrepare->prepare( $sql, ...$params );

    try {
      $translators = $this->queryHandler->query( $preparedSql )->getResults();
    } catch ( DatabaseErrorException $e ) {
      $translators = [];
    }

    $translatorsIds = array_map(
      function ( $translator ) {
        return $translator['ID'];
      },
      $translators
    );

    $translatorsLanguagePairs = $this->translatorLanguagePairsQuery->getForManyTranslators(
      $translatorsIds
    );

    $translators = array_filter(
      $translators,
      function ( $translator ) use ( $translatorsLanguagePairs ) {
        return isset( $translatorsLanguagePairs[ $translator['ID'] ] );
      }
    );

    return array_map(
      function ( $translator ) use ( $translatorsLanguagePairs ) {
        return new Translator(
          $translator['ID'],
          $translator['display_name'],
          $translator['user_nicename'],
          $translatorsLanguagePairs[ $translator['ID'] ]
        );
      },
      $translators
    );
  }


}
