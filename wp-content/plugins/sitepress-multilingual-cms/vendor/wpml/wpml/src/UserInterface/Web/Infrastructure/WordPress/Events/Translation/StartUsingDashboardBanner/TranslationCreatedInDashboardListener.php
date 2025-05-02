<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\Events\Translation\StartUsingDashboardBanner;

use WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Service\TranslationsFromDashboardService;

class TranslationCreatedInDashboardListener {

  /** @var TranslationsFromDashboardService */
  private $translationsFromDashboardService;


  public function __construct( TranslationsFromDashboardService $translationsFromDashboardService ) {
    $this->translationsFromDashboardService = $translationsFromDashboardService;

  }


  /**
   * @return void
   */
  public function recordTranslation() {
    $this->translationsFromDashboardService->recordTranslator();
  }


}
