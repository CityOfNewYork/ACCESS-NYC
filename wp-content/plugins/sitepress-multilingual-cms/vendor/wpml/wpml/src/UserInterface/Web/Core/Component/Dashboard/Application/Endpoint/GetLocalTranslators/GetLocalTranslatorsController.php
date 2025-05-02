<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetLocalTranslators;

use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\Core\SharedKernel\Component\Translator\Application\Service\Dto\TranslatorDto;
use WPML\Core\SharedKernel\Component\Translator\Application\Service\TranslatorsService;

class GetLocalTranslatorsController implements EndpointInterface {

  /** @var TranslatorsService */
  private $translatorsService;


  public function __construct( TranslatorsService $translatorsService ) {
    $this->translatorsService = $translatorsService;
  }


  public function handle( $requestData = null ): array {
    $localTranslators = array_map(
      function ( TranslatorDto $translatorDto ) {
        return $translatorDto->toArray();
      },
      $this->translatorsService->get()
    );

    return [
      'translators' => $localTranslators
    ];
  }


}
