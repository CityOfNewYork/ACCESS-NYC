<?php

namespace WPML\UserInterface\Web\Core\Component\ATE\Application\Endpoint;

use WPML\Core\Component\Translation\Application\Service\SettingsService;
use WPML\Core\Port\Endpoint\EndpointInterface;

class EnableAteController implements EndpointInterface {

  /**
   * @var SettingsService
   */
  private $settingsService;


  public function __construct( SettingsService $settingsService ) {
    $this->settingsService = $settingsService;
  }


  /**
   * @param array<string,mixed>|null $requestData
   *
   * @return array|mixed[]
   */
  public function handle( $requestData = null ): array {
    $this->settingsService->enableATE();

    return [ 'success' => true ];
  }


}
