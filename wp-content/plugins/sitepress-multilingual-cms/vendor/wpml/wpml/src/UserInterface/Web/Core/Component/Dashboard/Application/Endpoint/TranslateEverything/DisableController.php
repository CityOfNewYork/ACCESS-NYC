<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\TranslateEverything;

use WPML\Core\Component\Translation\Application\Service\SettingsService;
use WPML\Core\Port\Endpoint\EndpointInterface;

class DisableController implements EndpointInterface {

  /** @var SettingsService */
  private $settingsService;


  public function __construct( SettingsService $settingsService ) {
    $this->settingsService = $settingsService;
  }


  public function handle( $requestData = null ): array {
    $this->settingsService->disableTranslateEverything();

    return [
      'success' => true,
    ];
  }


}
