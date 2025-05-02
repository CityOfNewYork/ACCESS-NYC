<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\Events\Translation\StartUsingDashboardBanner;

use WPML\Core\Port\Event\EventListenerInterface;
use WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Service\ManualTranslationsCountService;

class BackFromATEManualTranslationListener implements EventListenerInterface {

  /** @var ManualTranslationsCountService */
  private $manualTranslationsCountService;


  public function __construct( ManualTranslationsCountService $manualTranslationsCountService ) {
    $this->manualTranslationsCountService = $manualTranslationsCountService;
  }


  /**
   * @return void
   */
  public function recordTranslation() {
    $this->manualTranslationsCountService->increment();
  }


}
