<?php

namespace WPML\UserInterface\Web\Core\Component\ATE\Application\Endpoint;

use WPML\Core\Component\ATE\Application\Query\GlossaryException;
use WPML\Core\Component\ATE\Application\Query\GlossaryInterface;
use WPML\Core\Port\Endpoint\EndpointInterface;

class GetGlossaryCountController implements EndpointInterface {

  /** @var GlossaryInterface */
  private $glossaryQuery;


  public function __construct( GlossaryInterface $glossaryQuery ) {
    $this->glossaryQuery = $glossaryQuery;
  }


  public function handle( $requestData = null ): array {
    try {
      return [
        'success' => true,
        'data'    => [
          'glossary_count' => $this->glossaryQuery->getGlossaryCount(),
        ]
      ];
    } catch ( GlossaryException $e ) {
      return [
        'success' => false,
        'message' => $e->getMessage(),
      ];
    }
  }


}
