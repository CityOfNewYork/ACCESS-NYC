<?php

namespace WPML\UserInterface\Web\Core\Component\Preferences\Application\Endpoint\SaveAutomaticTranslationsSettings;

use WPML\Core\Component\ATE\Application\Service\EngineServiceException;
use WPML\Core\Component\ATE\Application\Service\EnginesServiceInterface;
use WPML\Core\Component\Translation\Application\Repository\SettingsRepository;
use WPML\Core\Component\Translation\Application\Service\SettingsService;
use WPML\Core\Port\Endpoint\EndpointInterface;

/**
 * @phpstan-import-type EngineDtoArray from \WPML\Core\Component\ATE\Application\Service\Dto\EngineDto
 */
class SaveAutomaticTranslationsSettingsController implements EndpointInterface {

  /** @var SettingsRepository */
  private $settingsRepository;

  /** @var SettingsService */
  private $settingsService;

  /** @var EnginesServiceInterface */
  private $engineService;

  /** @var EnginesBuilder */
  private $enginesBuilder;


  public function __construct(
    SettingsRepository $settingsRepository,
    SettingsService $settingsService,
    EnginesServiceInterface $engineService,
    EnginesBuilder $enginesBuilder
  ) {
    $this->settingsRepository = $settingsRepository;
    $this->settingsService    = $settingsService;
    $this->engineService      = $engineService;
    $this->enginesBuilder     = $enginesBuilder;
  }


  /**
   * @param array<string,mixed>|null $requestData
   *
   * @return array<mixed, mixed>
   */
  public function handle( $requestData = null ): array {
    try {
      if (
        isset( $requestData['engines'] ) &&
        is_array( $requestData['engines'] ) &&
        ! empty( $requestData['engines'] )
      ) {
        $engines = $this->enginesBuilder->build( $requestData['engines'] );
        $this->engineService->update( $engines );
      }
    } catch ( EngineServiceException $e ) {
      return [
        'status'  => false,
        'message' => $e->getMessage(),
      ];
    }

    /**
     * If the engines save fails, we should return an error message and not save the other settings.
     */

    if ( isset( $requestData['reviewMode'] ) && is_string( $requestData['reviewMode'] ) ) {
      $this->settingsService->saveReviewOption( $requestData['reviewMode'] );
    }

    if ( isset( $requestData['shouldTranslateAutomaticallyDrafts'] ) ) {
      $this->settingsRepository->saveShouldTranslateAutomaticallyDrafts(
        (bool) $requestData['shouldTranslateAutomaticallyDrafts']
      );
    }

    return [
      'status' => true,
    ];
  }


}
