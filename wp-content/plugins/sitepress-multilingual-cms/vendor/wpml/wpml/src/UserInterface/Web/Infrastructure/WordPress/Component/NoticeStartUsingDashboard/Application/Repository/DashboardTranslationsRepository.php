<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\Component\NoticeStartUsingDashboard\Application\Repository;

use WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Repository\DashboardTranslationsRepositoryInterface;

final class DashboardTranslationsRepository implements DashboardTranslationsRepositoryInterface {
  const META_KEY = '_wpml_dashboard_translations';


  public function recordTranslator( int $translatorId ) {
    \update_user_meta( $translatorId, self::META_KEY, true );
  }


  public function doesTranslatorHaveAny( int $translatorId ): bool {
    return (bool) \get_user_meta( $translatorId, self::META_KEY, true );
  }


}
