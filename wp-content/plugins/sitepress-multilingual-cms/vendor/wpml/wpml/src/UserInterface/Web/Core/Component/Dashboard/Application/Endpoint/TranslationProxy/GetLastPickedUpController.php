<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\TranslationProxy;

use WPML\Core\Component\TranslationProxy\Application\Service\LastPickedUpDateServiceInterface;
use WPML\Core\Port\Endpoint\EndpointInterface;

class GetLastPickedUpController implements EndpointInterface {

  /** @var LastPickedUpDateServiceInterface */
  private $lastPickedUpService;


  public function __construct( LastPickedUpDateServiceInterface $lastPickedUpDateService ) {
    $this->lastPickedUpService = $lastPickedUpDateService;
  }


  public function handle( $requestData = null ): array {
    return [
      'lastPickedUp' => $this->lastPickedUpService->get(),
    ];
  }


}
