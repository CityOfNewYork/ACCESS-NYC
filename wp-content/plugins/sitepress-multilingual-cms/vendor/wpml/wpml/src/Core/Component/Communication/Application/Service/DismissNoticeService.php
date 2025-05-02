<?php

namespace WPML\Core\Component\Communication\Application\Service;

use WPML\Core\Component\Communication\Domain\Repository\DismissedNoticesRepository;

class DismissNoticeService {

  /** @var DismissedNoticesRepository */
  private $repository;


  public function __construct( DismissedNoticesRepository $repository ) {
    $this->repository = $repository;
  }


  /**
   * @param string $noticeId
   *
   * @return void
   */
  public function dismiss( string $noticeId ) {
    $this->repository->dismiss( $noticeId );
  }


  /**
   * @param string $noticeId
   * @param int $userId
   *
   * @return void
   */
  public function dismissPerUser( string $noticeId, int $userId ) {
    $this->repository->dismissPerUser( $noticeId, $userId );
  }


}
