<?php

namespace WPML\Core\Component\ATE\Application\Service\Dto\UpdateEngine;

use WPML\Core\Component\ATE\Application\Service\Dto\Engine\FormalityLevelDto;

class FormalitySettingDto {

  /** @var string */
  private $languageCode;

  /** @var FormalityLevelDto */
  private $currentLevel;


  public function __construct(
    string $languageCode,
    FormalityLevelDto $currentLevel
  ) {
    $this->languageCode = $languageCode;
    $this->currentLevel = $currentLevel;
  }


  public function getLanguageCode(): string {
    return $this->languageCode;
  }


  public function getCurrentLevel(): FormalityLevelDto {
    return $this->currentLevel;
  }


}
