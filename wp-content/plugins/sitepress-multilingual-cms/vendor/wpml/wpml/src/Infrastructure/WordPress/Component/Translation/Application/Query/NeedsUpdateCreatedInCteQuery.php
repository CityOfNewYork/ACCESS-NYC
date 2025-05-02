<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Query;

use WPML\Core\Component\StringPackage\Application\Query\PackageDefinitionQueryInterface;
use WPML\Core\Component\Translation\Application\Query\NeedsUpdateCreatedInCteQueryInterface;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;

class NeedsUpdateCreatedInCteQuery implements NeedsUpdateCreatedInCteQueryInterface {

  /** @phpstan-var QueryHandlerInterface<int, int> $queryHandler */
  private $queryHandler;

  /** @var QueryPrepareInterface $queryPrepare */
  private $queryPrepare;

  /** @var PackageDefinitionQueryInterface */
  private $packageDefinitionQuery;


  /**
   * @phpstan-param QueryHandlerInterface<int, int> $queryHandler
   *
   * @param QueryPrepareInterface                        $queryPrepare
   * @param PackageDefinitionQueryInterface              $packageDefinitionQuery
   */
  public function __construct(
    QueryHandlerInterface $queryHandler,
    QueryPrepareInterface $queryPrepare,
    PackageDefinitionQueryInterface $packageDefinitionQuery
  ) {
    $this->queryHandler           = $queryHandler;
    $this->queryPrepare           = $queryPrepare;
    $this->packageDefinitionQuery = $packageDefinitionQuery;
  }


  public function get(): int {
    /**
     * PERFORMANCE IMPROVEMENT: Add a "CTE was ever used on site" flag inside wp_option. If the flag is set to false
     * then we could skip the whole query.
     */

    $translatablePackages = $this->packageDefinitionQuery->getNamesList();
    $translatablePackages = array_map(
      function ( $package ) {
        return $this->queryPrepare->prepare( '%s', sprintf( 'package_%s', $package ) );
      },
      $translatablePackages
    );
    $translatablePackages = implode( ',', $translatablePackages );

    $packagesCondition = '';
    if ( ! empty( $translatablePackages ) ) {
      $packagesCondition = "OR original_translation.element_type IN ({$translatablePackages})";
    }

    $sql = "
      SELECT COUNT(DISTINCT original_translation.element_id, original_translation.element_type) as count
      FROM {$this->queryPrepare->prefix()}icl_translation_status translation_status
      
      INNER JOIN (
          SELECT rid, MAX(job_id) AS last_job_id
          FROM {$this->queryPrepare->prefix()}icl_translate_job
          GROUP BY rid
      ) last_jobs ON translation_status.rid = last_jobs.rid
      INNER JOIN {$this->queryPrepare->prefix()}icl_translate_job jobs ON jobs.job_id = last_jobs.last_job_id
      
      INNER JOIN {$this->queryPrepare->prefix()}icl_translations translations 
          ON translations.translation_id = translation_status.translation_id
      INNER JOIN {$this->queryPrepare->prefix()}icl_translations original_translation 
          ON original_translation.trid = translations.trid AND original_translation.source_language_code IS NULL
      
      WHERE translation_status.needs_update = 1
        AND jobs.editor = 'wpml'
        AND (
            original_translation.element_type LIKE 'post_%' {$packagesCondition}                
        )
    ";

    try {
      return (int) $this->queryHandler->querySingle( $sql );
    } catch ( DatabaseErrorException $e ) {
      return 0;
    }
  }


}
