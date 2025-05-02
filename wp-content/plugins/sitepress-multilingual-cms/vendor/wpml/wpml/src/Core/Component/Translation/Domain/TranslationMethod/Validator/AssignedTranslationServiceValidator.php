<?php

namespace WPML\Core\Component\Translation\Domain\TranslationMethod\Validator;

use WPML\Core\Component\Translation\Domain\TranslationMethod\TranslationServiceMethod;
use WPML\Core\SharedKernel\Component\TranslationProxy\Domain\Query\FetchRemoteTranslationServiceException;
use WPML\Core\SharedKernel\Component\TranslationProxy\Domain\Query\RemoteTranslationServiceQueryInterface;

class AssignedTranslationServiceValidator {

  /** @var RemoteTranslationServiceQueryInterface */
  private $remoteTranslationServiceQuery;


  public function __construct( RemoteTranslationServiceQueryInterface $remoteTranslationServiceQuery ) {
    $this->remoteTranslationServiceQuery = $remoteTranslationServiceQuery;
  }


  /**
   * @param TranslationServiceMethod[] $translationMethods
   *
   * @return bool
   *
   * @throws FetchRemoteTranslationServiceException
   */
  public function validate( array $translationMethods ): bool {
    if ( ! count( $translationMethods ) ) {
      return true;
    }

    $translationService = $this->remoteTranslationServiceQuery->getCurrent();

    $translationServiceActiveAndAuthenticated = $translationService
                                                && $translationService->isAuthenticated();

    if ( ! $translationServiceActiveAndAuthenticated ) {
      // If current translation service is not active and authenticated,
      // return validation result immediately.
      return false;
    }

    foreach ( $translationMethods as $translationServiceMethod ) {
      if ( ! $translationServiceMethod->getServiceId() ) {
        // If no translation service is assigned inside the translation service method,
        // return validation result immediately.
        return false;
      }

      $sameTranslationServiceAssigned = $translationService->getId() ===
                                        $translationServiceMethod->getServiceId();

      if ( ! $sameTranslationServiceAssigned ) {
        // If the assigned translation service isn't same as current active one,
        // return validation result immediately.
        return false;
      }
    }

    return true;
  }


}
