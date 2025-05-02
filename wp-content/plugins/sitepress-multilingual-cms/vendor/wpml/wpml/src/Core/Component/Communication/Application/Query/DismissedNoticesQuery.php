<?php

namespace WPML\Core\Component\Communication\Application\Query;

use WPML\Core\Component\Communication\Domain\DismissedNoticesStorageInterface;

class DismissedNoticesQuery {

  /** @var DismissedNoticesStorageInterface */
  private $storage;


  public function __construct( DismissedNoticesStorageInterface $storage ) {
    $this->storage = $storage;
  }


  /**
   * @param string[] $noticeIdsToCheck
   *
   * @return string[]
   */
  public function getDismissed( array $noticeIdsToCheck = [] ): array {
    $dismissed = $this->storage->getGlobal();

    if ( ! empty( $noticeIdsToCheck ) ) {
      $dismissed = array_intersect( $dismissed, $noticeIdsToCheck );
    }

    return $dismissed;
  }


  /**
   * @param int      $userId
   * @param string[] $noticeIdsToCheck
   *
   * @return string[]
   */
  public function getDismissedByUser( int $userId, array $noticeIdsToCheck = [] ): array {
    $dismissed = $this->storage->getPerUser( $userId );

    if ( ! empty( $noticeIdsToCheck ) ) {
      $dismissed = array_intersect( $dismissed, $noticeIdsToCheck );
    }

    return $dismissed;
  }


}
