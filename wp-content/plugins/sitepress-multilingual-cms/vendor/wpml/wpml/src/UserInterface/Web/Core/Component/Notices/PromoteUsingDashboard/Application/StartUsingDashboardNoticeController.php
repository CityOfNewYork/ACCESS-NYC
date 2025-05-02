<?php

namespace WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application;

use WPML\Core\SharedKernel\Component\User\Application\Query\UserQueryInterface;
use WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Service\NoticeVisibilityService;
use WPML\UserInterface\Web\Core\Port\Script\ScriptDataProviderInterface;
use WPML\UserInterface\Web\Core\Port\Script\ScriptPrerequisitesInterface;
use WPML\UserInterface\Web\Core\SharedKernel\Config\NoticeRenderInterface;
use WPML\UserInterface\Web\Core\SharedKernel\Config\NoticeRequirementsInterface;

class StartUsingDashboardNoticeController implements
  NoticeRenderInterface,
  NoticeRequirementsInterface,
  ScriptPrerequisitesInterface,
  ScriptDataProviderInterface {

  const NOTICE_ID = 'notice-promote-using-dashboard';

  /** @var NoticeVisibilityService */
  private $noticeVisibilityService;

  /** @var UserQueryInterface */
  private $userQuery;


  public function __construct(
    NoticeVisibilityService $noticeVisibilityService,
    UserQueryInterface $userQuery
  ) {
    $this->noticeVisibilityService = $noticeVisibilityService;
    $this->userQuery               = $userQuery;
  }


  public function render() {
    echo '<div id="' . self::NOTICE_ID . '"></div>';
  }


  public function requirementsMet() {
    return $this->noticeVisibilityService->isVisible();
  }


  public function scriptPrerequisitesMet(): bool {
    return true;
  }


  public function jsWindowKey(): string {
    return 'wpmlScriptData';
  }


  public function initialScriptData(): array {
    $currentUser = $this->userQuery->getCurrent();

    return [
      'noticeId'    => self::NOTICE_ID,
      'currentUser' => $currentUser ? $currentUser->toArray() : null,
      'urls'        => [
        'tmdashboard' => admin_url( 'admin.php?page=tm%2Fmenu%2Fmain.php' ),
      ],
    ];
  }


}
