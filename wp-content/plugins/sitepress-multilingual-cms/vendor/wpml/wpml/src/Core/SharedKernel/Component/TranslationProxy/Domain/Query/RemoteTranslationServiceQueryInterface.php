<?php

namespace WPML\Core\SharedKernel\Component\TranslationProxy\Domain\Query;

use WPML\Core\SharedKernel\Component\TranslationProxy\Domain\RemoteTranslationServiceDomain;

interface RemoteTranslationServiceQueryInterface {


  /**
   * @param bool $forceRefreshExtraFields
   *
   * @return RemoteTranslationServiceDomain|null
   * @throws FetchRemoteTranslationServiceException
   */
  public function getCurrent( bool $forceRefreshExtraFields = false );


}
