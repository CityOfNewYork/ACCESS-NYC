<?php

namespace WPML\Core\Component\Communication\Domain;

interface DismissedNoticesStorageInterface {


  /**
   * @param string $noticeId
   *
   * @return void
   */
  public function appendGlobal( string $noticeId );


  /**
   * @param string $noticeId
   * @param int    $userId
   *
   * @return void
   */
  public function appendPerUser( string $noticeId, int $userId );


  /**
   * @return string[]
   */
  public function getGlobal(): array;


  /**
   * @param int $userId
   *
   * @return string[]
   */
  public function getPerUser( int $userId ): array;


}
