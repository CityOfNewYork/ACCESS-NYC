<?php

namespace WPML\Infrastructure\WordPress\Component\Communication\Domain;

use WPML\Core\Component\Communication\Domain\DismissedNoticesStorageInterface;

class DismissedNoticesStorage implements DismissedNoticesStorageInterface {

  const OPTION_NAME = 'WPML(notices)';
  const USER_META_KEY = 'WPML(notices)';


  /**
   * @inheritDoc
   */
  public function appendGlobal( string $noticeId ) {
    $dismissedNotices   = $this->getGlobal();
    $dismissedNotices[] = $noticeId;
    $this->saveGlobal( $dismissedNotices );
  }


  /**
   * @inheritDoc
   */
  public function appendPerUser( string $noticeId, int $userId ) {
    $dismissedNotices   = $this->getPerUser( $userId );
    $dismissedNotices[] = $noticeId;
    $this->savePerUser( $dismissedNotices, $userId );
  }


  /**
   * @inheritDoc
   * @return array<string>
   */
  public function getGlobal(): array {
    /** @var array{dismissed?: array<string>} $options */
    $options = \get_option( self::OPTION_NAME, [] );

    return isset( $options['dismissed'] ) ? $options['dismissed'] : [];
  }


  /**
   * @inheritDoc
   * @return array<string>
   */
  public function getPerUser( int $userId ): array {
    /** @var array<string>|false $meta */
    $meta = \get_user_meta( $userId, self::USER_META_KEY, true );

    return is_array( $meta ) ? $meta : [];
  }


  /**
   * @param array<string> $dismissedNotices
   *
   * @return void
   */
  private function saveGlobal( array $dismissedNotices ) {
    /** @var array{dismissed?: array<string>} $options */
    $options = \get_option( self::OPTION_NAME, [] );
    $options['dismissed'] = $dismissedNotices;
    \update_option( self::OPTION_NAME, $options );
  }


  /**
   * @param array<string> $dismissedNotices
   * @param int      $userId
   *
   * @return void
   */
  private function savePerUser( array $dismissedNotices, int $userId ) {
    \update_user_meta( $userId, self::USER_META_KEY, $dismissedNotices );
  }


}
