<?php

namespace WPML\Core\Component\Communication\Domain\Repository;

use WPML\Core\Component\Communication\Domain\DismissedNoticesStorageInterface;

class DismissedNoticesRepository {

  /** @var DismissedNoticesStorageInterface */
  private $storage;


  public function __construct( DismissedNoticesStorageInterface $storage ) {
    $this->storage = $storage;
  }


  /**
   * @param string $noticeId
   *
   * @return void
   */
  public function dismiss( string $noticeId ) {
    $dismissedNotices = $this->storage->getGlobal();

    if ( ! in_array( $noticeId, $dismissedNotices ) ) {
      $this->storage->appendGlobal( $noticeId );
    }
  }


  /**
   * @param string $noticeId
   * @param int    $userId
   *
   * @return void
   */
  public function dismissPerUser( string $noticeId, int $userId ) {
    $dismissedNotices = $this->storage->getPerUser( $userId );

    if ( ! in_array( $noticeId, $dismissedNotices ) ) {
      $this->storage->appendPerUser( $noticeId, $userId );
    }
  }


}
