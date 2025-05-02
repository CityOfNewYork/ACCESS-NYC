<?php

namespace WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Service;

use WPML\Core\Component\Communication\Application\Query\DismissedNoticesQuery;
use WPML\Core\SharedKernel\Component\User\Application\Query\UserQueryInterface;

final class NoticeVisibilityService {

  const COUNT_THRESHOLD = 5;

  /** @var ManualTranslationsCountService */
  private $manualTranslationsCountService;

  /** @var TranslationsFromDashboardService */
  private $translationsFromDashboardService;

  /** @var DismissedNoticesQuery */
  private $dismissQuery;

  /** @var UserQueryInterface */
  private $userQuery;


  public function __construct(
    ManualTranslationsCountService $manualTranslationsCountService,
    TranslationsFromDashboardService $translationsFromDashboardService,
    DismissedNoticesQuery $dismissQuery,
    UserQueryInterface $userQuery
  ) {
    $this->manualTranslationsCountService   = $manualTranslationsCountService;
    $this->translationsFromDashboardService = $translationsFromDashboardService;
    $this->dismissQuery                     = $dismissQuery;
    $this->userQuery                        = $userQuery;
  }


  public function isVisible(): bool {
    $user = $this->userQuery->getCurrent();
    if ( ! $user ) {
      return false;
    }

    return $this->manualTranslationsCountService->count() >= self::COUNT_THRESHOLD
      && ! $this->translationsFromDashboardService->hasAny()
      && ! count( $this->dismissQuery->getDismissedByUser( $user->getId(), [ 'notice-promote-using-dashboard' ] ) ) > 0;
  }


}
