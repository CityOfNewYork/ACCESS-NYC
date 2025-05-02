<?php

namespace WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Service;

use WPML\Core\SharedKernel\Component\User\Application\Query\UserQueryInterface;
use WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Repository\DashboardTranslationsRepositoryInterface;

final class TranslationsFromDashboardService {

  /**
   * IMPORTANT!
   * We have to use UserQueryInterface, not TranslatorsQueryInterface.
   * A user who is NOT translator, but is an admin is still able to click "+" on the post list.
   *
   * @var UserQueryInterface
   */
  private $userQuery;

  /** @var DashboardTranslationsRepositoryInterface */
  private $dashboardTranslationsRepository;


  public function __construct(
    UserQueryInterface $userQuery,
    DashboardTranslationsRepositoryInterface $dashboardTranslationsRepository
  ) {
    $this->userQuery                      = $userQuery;
    $this->dashboardTranslationsRepository = $dashboardTranslationsRepository;
  }


  /**
   * Record a translation created from Translation Dashboard by current translator.
   *
   * @return void
   */
  public function recordTranslator() {
    $translator = $this->userQuery->getCurrent();
    if ( ! $translator ) {
      return;
    }

    $this->dashboardTranslationsRepository->recordTranslator( $translator->getId() );
  }


  public function hasAny(): bool {
    $translator = $this->userQuery->getCurrent();
    if ( ! $translator ) {
      return false;
    }

    return $this->dashboardTranslationsRepository->doesTranslatorHaveAny( $translator->getId() );
  }


}
