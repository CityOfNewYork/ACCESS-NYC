<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetLocalTranslatorById;

use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\Core\SharedKernel\Component\Translator\Application\Service\TranslatorsService;

class GetTranslatorByIdController implements EndpointInterface {

  /** @var TranslatorsService */
  private $translatorsService;


  public function __construct( TranslatorsService $translatorsService ) {
    $this->translatorsService = $translatorsService;
  }


  /**
   * @param array<string, mixed> $requestData
   *
   * @return array<string, mixed>
   */
  public function handle( $requestData = null ): array {
    /** @var int $translatorId */
    $translatorId = $requestData['translatorId'] ?? null;

    if ( ! $translatorId ) {
      return [ 'translator' => null ];
    }

    $translator = $this->translatorsService->getById( $translatorId );

    return $translator ?
      [ 'translator' => $translator->toArray() ] :
      [ 'translator' => null ];
  }


}
