<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetRemoteTranslationService;

use WPML\Core\Component\TranslationProxy\Application\Service\RemoteTranslationService;
use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\Core\SharedKernel\Component\TranslationProxy\Domain\Query\FetchRemoteTranslationServiceException;

class GetRemoteTranslationServiceController implements EndpointInterface {

  /** @var RemoteTranslationService */
  private $remoteTranslationServiceService;


  public function __construct( RemoteTranslationService $remoteTranslationServiceService ) {
    $this->remoteTranslationServiceService = $remoteTranslationServiceService;
  }


  public function handle( $requestData = null ): array {
    try {
      /** @var bool $forceRefreshExtraFields */
      $forceRefreshExtraFields = isset( $requestData['forceRefreshExtraFields'] )
                                 && $requestData['forceRefreshExtraFields'] === 'true';

      $translationService = $this->remoteTranslationServiceService->getCurrent( $forceRefreshExtraFields );

      return [
        'translationService' => $translationService ?
          $translationService->toArray() :
          null,
      ];
    } catch ( FetchRemoteTranslationServiceException $e ) {
      return [ 'translationService' => null ];
    }
  }


}
