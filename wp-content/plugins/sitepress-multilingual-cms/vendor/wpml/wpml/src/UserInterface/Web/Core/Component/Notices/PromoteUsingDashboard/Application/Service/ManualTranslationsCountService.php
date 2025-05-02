<?php

namespace WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Service;

use WPML\Core\SharedKernel\Component\User\Application\Query\UserQueryInterface;
use WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Repository\ManualTranslationsCountRepositoryInterface;
use WPML\UserInterface\Web\Core\SharedKernel\Config\ExistingPageInterface;

final class ManualTranslationsCountService {

  /** @var ManualTranslationsCountRepositoryInterface */
  private $repository;

  /**
   * IMPORTANT!
   * We have to use UserQueryInterface, not TranslatorsQueryInterface.
   * A user who is NOT translator, but is an admin is still able to click "+" on the post list.
   *
   * @var UserQueryInterface
   */
  private $userQuery;

  /** @var ExistingPageInterface[] */
  private $allowedPages;


  /**
   * @param ManualTranslationsCountRepositoryInterface $repository
   * @param UserQueryInterface                         $userQuery
   * @param ExistingPageInterface[]                    $allowedPages
   */
  public function __construct(
    ManualTranslationsCountRepositoryInterface $repository,
    UserQueryInterface $userQuery,
    array $allowedPages
  ) {
    $this->repository   = $repository;
    $this->userQuery    = $userQuery;
    $this->allowedPages = $allowedPages;
  }


  public function count(): int {
    $translator = $this->userQuery->getCurrent();
    if ( ! $translator ) {
      return 0;
    }

    return $this->repository->count( $translator->getId() );
  }


  /**
   * @return void
   */
  public function increment() {
    $translator = $this->userQuery->getCurrent();
    if ( ! $translator ) {
      return;
    }

    if ( $this->isCurrentPageAllowed() ) {
      $this->repository->increment( $translator->getId() );
    }
  }


  private function isCurrentPageAllowed(): bool {
    foreach ( $this->allowedPages as $page ) {
      if ( $page->isActive() ) {
        return true;
      }
    }

    return false;
  }


}
