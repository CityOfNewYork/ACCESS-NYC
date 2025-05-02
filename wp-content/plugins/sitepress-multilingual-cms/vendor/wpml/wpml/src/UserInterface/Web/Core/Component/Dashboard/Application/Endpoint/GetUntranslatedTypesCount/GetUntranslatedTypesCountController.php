<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetUntranslatedTypesCount;

use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\Core\SharedKernel\Component\Item\Application\Query\UntranslatedTypesCountQueryInterface;

class GetUntranslatedTypesCountController implements EndpointInterface {

  /** @var UntranslatedTypesCountQueryInterface[] */
  private $queries;


  /**
   * @param UntranslatedTypesCountQueryInterface[] $queries
   */
  public function __construct( array $queries ) {
    $this->queries = $queries;
  }


  public function handle( $requestData = null ): array {
    $counts = [];

    foreach ( $this->queries as $query ) {
      $counts = array_merge( $counts, $query->get() );
    }

    return array_map(
      function ( $count ) {
        return $count->toArray();
      },
      $counts
    );
  }


}
