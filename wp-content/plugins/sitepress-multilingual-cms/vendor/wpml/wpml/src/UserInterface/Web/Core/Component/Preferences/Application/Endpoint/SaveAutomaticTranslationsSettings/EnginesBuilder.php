<?php

namespace WPML\UserInterface\Web\Core\Component\Preferences\Application\Endpoint\SaveAutomaticTranslationsSettings;

use WPML\Core\Component\ATE\Application\Service\Dto\Engine\FormalityLevelDto;
use WPML\Core\Component\ATE\Application\Service\Dto\UpdateEngine\FormalitySettingDto;
use WPML\Core\Component\ATE\Application\Service\Dto\UpdateEngineDto;
use WPML\Core\Component\ATE\Application\Service\EngineServiceException;

/**
 * @phpstan-type PartialEngineDtoArray array{
 *   engine?: string,
 *   enabled?: bool,
 *   formalityAvailable?: bool,
 *    formalitySettings?: array{languageCode?: string, currentLevel?: string}[]|mixed|null
 *  }
 */
class EnginesBuilder {


  /**
   * @param PartialEngineDtoArray[] $rawEngines
   *
   * @return UpdateEngineDto[]
   * @throws EngineServiceException
   */
  public function build( array $rawEngines ): array {
    $engines = [];

    foreach ( $rawEngines as $rawEngine ) {

      if ( ! isset( $rawEngine['engine'], $rawEngine['enabled'], $rawEngine['formalityAvailable'] ) ) {
        throw new EngineServiceException( 'Invalid engine data' );
      }

      $engine = new UpdateEngineDto(
        $rawEngine['engine'],
        $rawEngine['enabled'],
        $rawEngine['formalityAvailable'],
        $this->buildFormalitySettings( $rawEngine )
      );

      $engines[] = $engine;
    }

    return $engines;
  }


  /**
   * @param PartialEngineDtoArray $rawEngine
   *
   * @return FormalitySettingDto[]|null
   * @throws EngineServiceException
   */
  private function buildFormalitySettings( array $rawEngine ) {
    $formalitySettings = null;

    if ( isset( $rawEngine['formalitySettings'] ) && is_array( $rawEngine['formalitySettings'] ) ) {
      $formalitySettings = [];

      foreach ( $rawEngine['formalitySettings'] as $formalitySetting ) {
        if ( ! isset( $formalitySetting['languageCode'], $formalitySetting['currentLevel'] ) ) {
          throw new EngineServiceException( 'Invalid formality setting data' );
        }

        if ( ! FormalityLevelDto::isValid( $formalitySetting['currentLevel'] ) ) {
          throw new EngineServiceException( 'Invalid formality level' );
        }

        $formalitySettings[] = new FormalitySettingDto(
          $formalitySetting['languageCode'],
          new FormalityLevelDto( $formalitySetting['currentLevel'] )
        );
      }
    }

    return $formalitySettings;

  }


}
