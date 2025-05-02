<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\SetReviewTranslationOption;

use WPML\Core\Component\Translation\Application\Service\SettingsService;
use WPML\Core\Port\Endpoint\EndpointInterface;

class SetReviewTranslationOptionController implements EndpointInterface {

  /** @var SettingsService */
  private $settingsService;


  public function __construct( SettingsService $settingsService ) {
    $this->settingsService = $settingsService;
  }


  /**
   * @param array<string,string> $requestData
   *
   * @psalm-suppress MoreSpecificImplementedParamType
   *
   * @return array<string, string|null>
   */
  public function handle( $requestData = null ): array {
    $requestData = $requestData ?: [];

    $reviewMode = $this->settingsService->saveReviewOption(
      $requestData['reviewOption'] ?? null
    );

    return [
      'reviewOption' => $reviewMode,
    ];
  }


}
