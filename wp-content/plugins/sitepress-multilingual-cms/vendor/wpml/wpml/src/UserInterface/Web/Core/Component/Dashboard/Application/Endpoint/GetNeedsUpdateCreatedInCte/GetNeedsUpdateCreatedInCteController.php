<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetNeedsUpdateCreatedInCte;

use WPML\Core\Component\Translation\Application\Query\NeedsUpdateCreatedInCteQueryInterface;
use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\PHP\Exception\Exception;
use WPML\PHP\Exception\InvalidArgumentException;

class GetNeedsUpdateCreatedInCteController implements EndpointInterface {

  /** @var NeedsUpdateCreatedInCteQueryInterface */
  private $query;


  public function __construct( NeedsUpdateCreatedInCteQueryInterface $query ) {
    $this->query = $query;
  }


  /**
   * @param array<string,mixed> $requestData
   *
   * @return array<string,mixed>
   * @throws InvalidArgumentException|Exception
   */
  public function handle( $requestData = null ): array {
    $count = $this->query->get();

    return [
      'success' => true,
      'data'    => $count
    ];
  }


}
