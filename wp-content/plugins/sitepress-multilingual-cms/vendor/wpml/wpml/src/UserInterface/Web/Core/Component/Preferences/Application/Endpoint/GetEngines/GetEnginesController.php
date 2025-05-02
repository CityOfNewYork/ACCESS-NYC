<?php

namespace WPML\UserInterface\Web\Core\Component\Preferences\Application\Endpoint\GetEngines;

use WPML\Core\Component\ATE\Application\Service\EngineServiceException;
use WPML\Core\Component\ATE\Application\Service\EnginesServiceInterface;
use WPML\Core\Port\Endpoint\EndpointInterface;

class GetEnginesController implements EndpointInterface {

  /** @var EnginesServiceInterface */
  private $enginesService;


  public function __construct( EnginesServiceInterface $enginesService ) {
    $this->enginesService = $enginesService;
  }


  /**
   * @param array<string,mixed>|null $requestData
   *
   * @return array<mixed, mixed>
   */
  public function handle( $requestData = null ): array {
    try {
      $enginesDto = $this->enginesService->getList();

      $engines = [];
      foreach ( $enginesDto as $engineDto ) {
        $engines[] = $engineDto->toArray();
      }

      return [
        'status' => true,
        'data'   => $engines,
      ];
    } catch ( EngineServiceException $e ) {
      return [
        'status'  => false,
        'message' => $e->getMessage(),
      ];
    }
  }


}
