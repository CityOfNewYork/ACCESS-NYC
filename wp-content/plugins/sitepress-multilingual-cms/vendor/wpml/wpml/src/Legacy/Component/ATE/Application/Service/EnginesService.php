<?php

namespace WPML\Legacy\Component\ATE\Application\Service;

use WPML\Core\Component\ATE\Application\Service\Dto\Engine\FormalityLevelDto;
use WPML\Core\Component\ATE\Application\Service\Dto\Engine\FormalitySettingDto;
use WPML\Core\Component\ATE\Application\Service\Dto\EngineDto;
use WPML\Core\Component\ATE\Application\Service\Dto\UpdateEngine\FormalitySettingDto as UpdateEngineFormalitySettingDto;
use WPML\Core\Component\ATE\Application\Service\Dto\UpdateEngineDto;
use WPML\Core\Component\ATE\Application\Service\EngineServiceException;
use WPML\Core\Component\ATE\Application\Service\EnginesServiceInterface;
use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;

/**
 * @phpstan-type LanguageFormalityInputArray array{
 *    lang_code: string,
 *    formality: string
 *  }
 *
 * @phpstan-type EngineDtoInputArray array{
 *    engine: string,
 *    order: int,
 *    formal_name: string,
 *    cost: int|null,
 *    enabled: bool,
 *    formality_available: bool,
 *    formality_settings: array{ languages: LanguageFormalityInputArray[]}|null
 *  }
 *
 * @phpstan-type AvailableFormalitiesOfLanguageArray array{
 *   formality_enabled: bool,
 *   available_formalities: string[]
 * }
 *
 * @phpstan-type AvailableFormalitiesOfEngineArray array{
 *    languages: array<string, AvailableFormalitiesOfLanguageArray>
 * }
 *
 * @phpstan-type AvailableFormalitiesArray array<string, AvailableFormalitiesOfEngineArray>
 */
class EnginesService implements EnginesServiceInterface {

  /**
   * @var \WPML_TM_AMS_API
   */
  private $amsApi;

  /** @var LanguagesQueryInterface */
  private $languagesQuery;


  public function __construct( \WPML_TM_AMS_API $amsApi, LanguagesQueryInterface $languagesQuery ) {
    $this->amsApi         = $amsApi;
    $this->languagesQuery = $languagesQuery;
  }


  /**
   * @return EngineDto[]
   * @throws EngineServiceException
   */
  public function getList(): array {
    $engines              = $this->fetchEnginesData();
    $availableFormalities = $this->fetchAvailableFormalities();

    $result = [];
    foreach ( $engines as $engineData ) {
      $availableFormalitiesOfEngine = $availableFormalities[ $engineData['engine'] ]['languages'] ?? [];
      $result[]                     = $this->buildEngineFromArray( $engineData, $availableFormalitiesOfEngine );
    }

    return $result;
  }


  /**
   * @param UpdateEngineDto[] $engines
   *
   * @return void
   * @throws EngineServiceException
   *
   */
  public function update( array $engines ) {
    $enginesData = [];
    foreach ( $engines as $engine ) {
      $engineRaw = [
        'engine'              => $engine->getEngine(),
        'enabled'             => $engine->isEnabled(),
        'formality_available' => $engine->isFormalityAvailable(),
      ];

      if ( $engine->isFormalityAvailable() && $engine->getFormalitySettings() ) {
        /** @var UpdateEngineFormalitySettingDto[] $formalitySettings */
        $formalitySettings = $engine->getFormalitySettings();

        $formalitySettingsData = [];
        foreach ( $formalitySettings as $formalitySetting ) {
          $formalitySettingsData[] = [
            'lang_code' => $formalitySetting->getLanguageCode(),
            'formality' => $formalitySetting->getCurrentLevel()->getValue(),
          ];
        }
        $engineRaw['formality_settings'] = [ 'languages' => $formalitySettingsData ];
      }

      $enginesData[] = $engineRaw;
    }

    try {
      $result = $this->amsApi->update_translation_engines( $enginesData );

      if ( is_wp_error( $result ) ) {
        throw new EngineServiceException();
      }

      if ( ! $result ) {
        throw new EngineServiceException();
      }
    } catch ( \Throwable $e ) {
      throw new EngineServiceException( 'The engines settings could not be saved' );
    }
  }


  /**
   * @return EngineDtoInputArray[]
   * @throws EngineServiceException
   */
  private function fetchEnginesData(): array {
    $apiResult = $this->amsApi->get_translation_engines();

    if ( is_wp_error( $apiResult ) ) {
      throw new EngineServiceException(
        $apiResult->get_error_message()
      );
    }

    if ( ! is_array( $apiResult ) || ! isset( $apiResult['list'] ) ) {
      throw new EngineServiceException(
        __( 'Error fetching translation engines', 'wpml' )
      );
    }

    return $apiResult['list'];
  }


  /**
   * @return AvailableFormalitiesArray
   * @throws EngineServiceException
   */
  private function fetchAvailableFormalities(): array {
    $apiResult = $this->amsApi->get_available_formalities();

    if ( is_wp_error( $apiResult ) ) {
      throw new EngineServiceException(
        $apiResult->get_error_message()
      );
    }

    if ( ! is_array( $apiResult ) || ! isset( $apiResult['engines'] ) ) {
      throw new EngineServiceException(
        __( 'Error fetching available formalities', 'wpml' )
      );
    }

    return $apiResult['engines'];
  }


  /**
   * @param EngineDtoInputArray                                $enginesData
   * @param array<string, AvailableFormalitiesOfLanguageArray> $availableFormalitiesOfEngine
   *
   * @return EngineDto
   */
  private function buildEngineFromArray( array $enginesData, array $availableFormalitiesOfEngine ): EngineDto {
    $engine             = $enginesData['engine'];
    $formalName         = $enginesData['formal_name'];
    $cost               = $enginesData['cost'] ?? 0;
    $enabled            = $enginesData['enabled'];
    $formalityAvailable = $enginesData['formality_available'];

    $engine = new EngineDto(
      $engine,
      $formalName,
      $cost,
      $enabled,
      $formalityAvailable
    );

    if ( $formalityAvailable ) {
      $currentFormalitySettings = $this->getEngineCurrentFormalitySettingsGroupedByLanguageCode( $enginesData );
      $formalitySettings        = [];

      foreach ( $this->languagesQuery->getSecondary() as $languageDto ) {
        $availableFormalitiesOfLanguage = $availableFormalitiesOfEngine[ $languageDto->getCode() ] ?? [];
        $availableLanguageLevels        = array_map(
          function ( string $levelValue ) {
            return new FormalityLevelDto( $levelValue );
          },
          $availableFormalitiesOfLanguage['available_formalities'] ?? []
        );

        $formalityLevel      = $currentFormalitySettings[ $languageDto->getCode() ] ?? FormalityLevelDto::default();
        $formalitySettings[] = new FormalitySettingDto(
          $languageDto->getCode(),
          $formalityLevel,
          $availableFormalitiesOfLanguage['formality_enabled'] ?? false,
          $availableLanguageLevels
        );
      }
      $engine->setFormalitySettings( $formalitySettings );
    }

    return $engine;
  }


  /**
   * @param EngineDtoInputArray $enginesData
   *
   * @return array<string, FormalityLevelDto> lang code => FormalityLevelDto
   */
  private function getEngineCurrentFormalitySettingsGroupedByLanguageCode( array $enginesData ): array {
    $formalitySettingsRaw = $enginesData['formality_settings']['languages'] ?? [];

    $result = [];
    foreach ( $formalitySettingsRaw as $row ) {
      $result[ $row['lang_code'] ] = new FormalityLevelDto( $row['formality'] );
    }

    return $result;
  }


}
