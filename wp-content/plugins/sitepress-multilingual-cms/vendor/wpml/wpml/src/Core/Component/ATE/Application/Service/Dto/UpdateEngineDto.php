<?php

namespace WPML\Core\Component\ATE\Application\Service\Dto;

use WPML\Core\Component\ATE\Application\Service\Dto\UpdateEngine\FormalitySettingDto;

class UpdateEngineDto {

  /** @var string */
  private $engine;

  /** @var bool */
  private $enabled;

  /** @var bool */
  private $formalityAvailable;

  /** @var FormalitySettingDto[]|null */
  private $formalitySettings;


  /**
   * @param string     $engine
   * @param bool       $enabled
   * @param bool       $formalityAvailable
   * @param FormalitySettingDto[]|null $formalitySettings
   */
  public function __construct(
    string $engine,
    bool $enabled,
    bool $formalityAvailable,
    array $formalitySettings = null
  ) {
    $this->engine             = $engine;
    $this->enabled            = $enabled;
    $this->formalityAvailable = $formalityAvailable;
    $this->formalitySettings  = $formalitySettings;
  }


  public function getEngine(): string {
    return $this->engine;
  }


  public function isEnabled(): bool {
    return $this->enabled;
  }


  public function isFormalityAvailable(): bool {
    return $this->formalityAvailable;
  }


  /**
   * @return FormalitySettingDto[]|null
   */
  public function getFormalitySettings() {
    return $this->formalitySettings;
  }


}
