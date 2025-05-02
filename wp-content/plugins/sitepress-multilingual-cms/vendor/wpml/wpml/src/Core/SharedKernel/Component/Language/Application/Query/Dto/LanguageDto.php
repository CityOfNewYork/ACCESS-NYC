<?php

namespace WPML\Core\SharedKernel\Component\Language\Application\Query\Dto;

class LanguageDto {

  /** @var string */
  private $code;

  /** @var string */
  private $englishName;

  /** @var string */
  private $nativeName;

  /** @var string */
  private $displayName;

  /** @var string|null */
  private $countryFlagUrl;

  /** @var bool */
  private $isActivated;

  /** @var bool|null */
  private $supportsAutomaticTranslations = null;


  public function __construct(
    string $code,
    string $englishName,
    string $nativeName,
    bool $isActivated = true
  ) {
    $this->code        = $code;
    $this->englishName = $englishName;
    $this->nativeName  = $nativeName;
    $this->isActivated = $isActivated;
    $this->displayName = $englishName;
  }


  public function getCode(): string {
    return $this->code;
  }


  public function getEnglishName(): string {
    return $this->englishName;
  }


  public function getNativeName(): string {
    return $this->nativeName;
  }


  /** @return ?string */
  public function getCountryFlagUrl() {
    return $this->countryFlagUrl;
  }


  public function isActivated(): bool {
    return $this->isActivated;
  }


  /**
   * @return void
   */
  public function setCountryFlagUrl( string $countryFlagUrl ) {
    $this->countryFlagUrl = $countryFlagUrl;
  }


  public function getDisplayName(): string {
    return $this->displayName;
  }


  /**
   * @return void
   */
  public function setDisplayName( string $displayName ) {
    $this->displayName = $displayName;
  }


  /**
   * @return bool|null
   */
  public function doesSupportAutomaticTranslations() {
    return $this->supportsAutomaticTranslations;
  }


  /**
   * @param bool|null $supportsAutomaticTranslations
   *
   * @return void
   */
  public function setSupportsAutomaticTranslations( bool $supportsAutomaticTranslations = null ) {
    $this->supportsAutomaticTranslations = $supportsAutomaticTranslations;
  }


}
