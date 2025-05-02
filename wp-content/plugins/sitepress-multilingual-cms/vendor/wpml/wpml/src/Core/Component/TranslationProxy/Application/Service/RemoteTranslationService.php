<?php

namespace WPML\Core\Component\TranslationProxy\Application\Service;

use WPML\Core\SharedKernel\Component\TranslationProxy\Domain\Query\FetchRemoteTranslationServiceException;
use WPML\Core\SharedKernel\Component\TranslationProxy\Domain\Query\RemoteTranslationServiceQueryInterface;
use WPML\Core\SharedKernel\Component\TranslationProxy\Domain\RemoteTranslationServiceDomain;

class RemoteTranslationService {

  /** @var RemoteTranslationServiceQueryInterface */
  private $remoteTranslationServiceQuery;


  public function __construct( RemoteTranslationServiceQueryInterface $remoteTranslationServiceQuery ) {
    $this->remoteTranslationServiceQuery = $remoteTranslationServiceQuery;
  }


  /**
   * @param bool $forceRefreshExtraFields
   *
   * @return RemoteTranslationServiceDomain|null
   * @throws FetchRemoteTranslationServiceException
   */
  public function getCurrent( bool $forceRefreshExtraFields = false ) {
    return $this->remoteTranslationServiceQuery->getCurrent( $forceRefreshExtraFields );
  }


}
