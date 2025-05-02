<?php

namespace WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Repository;

/**
 * Repository counts manual ATE translations performed by clicking the "+" icon
 * from the post listing page or from post edit page.
 */
interface ManualTranslationsCountRepositoryInterface {


  public function count( int $translatorId ): int;


  /**
   * @param int $translatorId
   *
   * @return void
   */
  public function increment( int $translatorId );


}
