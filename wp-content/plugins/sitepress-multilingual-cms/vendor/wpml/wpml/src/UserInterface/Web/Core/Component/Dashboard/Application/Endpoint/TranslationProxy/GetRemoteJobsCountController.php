<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\TranslationProxy;

use WPML\Core\Component\TranslationProxy\Application\Query\RemoteJobsQueryInterface;
use WPML\Core\Component\TranslationProxy\Application\Service\RemoteTranslationService;
use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\Core\SharedKernel\Component\TranslationProxy\Domain\Query\FetchRemoteTranslationServiceException;

class GetRemoteJobsCountController implements EndpointInterface {

  /** @var RemoteTranslationService */
  private $remoteTranslationServiceService;

  /** @var RemoteJobsQueryInterface */
  private $remoteJobsQuery;


  public function __construct(
    RemoteTranslationService $remoteTranslationServiceService,
    RemoteJobsQueryInterface $remoteJobsQuery
  ) {
    $this->remoteTranslationServiceService = $remoteTranslationServiceService;
    $this->remoteJobsQuery                 = $remoteJobsQuery;
  }


  /**
   * @param array<string,mixed>|null $requestData
   *
   * @return array<string, int>
   * @throws FetchRemoteTranslationServiceException
   */
  public function handle( $requestData = null ): array {
    $currentTranslationService = $this->remoteTranslationServiceService->getCurrent();

    if ( ! $currentTranslationService ) {
      return [ 'count' => 0 ];
    }

    return [
      'count' => $this->remoteJobsQuery->getCount(
        $currentTranslationService->getId()
      )
    ];
  }


}
