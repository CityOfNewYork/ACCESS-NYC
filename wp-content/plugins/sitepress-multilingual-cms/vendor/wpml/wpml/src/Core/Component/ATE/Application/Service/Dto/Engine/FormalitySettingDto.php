<?php

namespace WPML\Core\Component\ATE\Application\Service\Dto\Engine;

/**
 * @phpstan-type FormalitySettingDtoArray array{
 *  languageCode: string,
 *  currentLevel: 'more'|'less'|'default',
 *  enabled: bool,
 *  availableLevels: array<'more'|'less'|'default'>
 * }
 */
class FormalitySettingDto {

  /** @var string */
  private $languageCode;

  /** @var FormalityLevelDto */
  private $currentLevel;

  /** @var bool */
  private $enabled;

  /** @var FormalityLevelDto[] */
  private $availableLevels;


  /**
   * @param string              $languageCode
   * @param FormalityLevelDto   $currentLevel
   * @param bool                $enabled
   * @param FormalityLevelDto[] $availableLevels
   */
  public function __construct(
    string $languageCode,
    FormalityLevelDto $currentLevel,
    bool $enabled = true,
    array $availableLevels = []
  ) {
    $this->languageCode    = $languageCode;
    $this->currentLevel    = $currentLevel;
    $this->enabled         = $enabled;
    $this->availableLevels = $availableLevels;
  }


  public function getLanguageCode(): string {
    return $this->languageCode;
  }


  public function getCurrentLevel(): FormalityLevelDto {
    return $this->currentLevel;
  }


  public function isEnabled(): bool {
    return $this->enabled;
  }


  /**
   * @return FormalityLevelDto[]
   */
  public function getAvailableLevels(): array {
    return $this->availableLevels;
  }


  /**
   * @return FormalitySettingDtoArray
   */
  public function toArray(): array {
    $availableLevels = array_map(
      function ( FormalityLevelDto $level ) {
        return $level->getValue();
      },
      $this->availableLevels
    );

    return [
      'languageCode'    => $this->languageCode,
      'currentLevel'    => $this->currentLevel->getValue(),
      'enabled'         => $this->enabled,
      'availableLevels' => $availableLevels,
    ];
  }


}
