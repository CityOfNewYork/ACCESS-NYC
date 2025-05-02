<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\Component\NoticeStartUsingDashboard\Application\Repository;

use WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Repository\ManualTranslationsCountRepositoryInterface;

class ManualTranslationsCountRepository implements ManualTranslationsCountRepositoryInterface {

  const META_KEY = '_wpml_manual_translations_count';


  public function count( int $translatorId ): int {
    $count = get_user_meta( $translatorId, self::META_KEY, true );

    return is_numeric( $count ) ? (int) $count : 0;
  }


  public function increment( int $translatorId ) {
    $currentCount = $this->count( $translatorId );
    update_user_meta( $translatorId, self::META_KEY, $currentCount + 1 );
  }


}
