<?php

namespace WPML\Core\SharedKernel\Component\Translation\Domain;

class TranslationEditorSetting {

  const ATE = 'ATE';
  const CLASSIC = 'CTE';
  const MANUAL = 'MANUAL';
  const PRO = 'PRO';

  /** @var string */
  private $value;

  /**
   * It determines if the ATE should be used for old translations created with CTE or we should stick to CTE.
   *
   * @var bool
   */
  private $useAteForOldTranslationsCreatedWithCte = false;


  /**
   * @param $value string
   */
  public function __construct( string $value ) {
    $this->value = in_array( $value, $this->getAll() ) ? $value : self::CLASSIC;
  }


  /**
   * @return string[]
   */
  public function getAll(): array {
    return [
      self::ATE,
      self::CLASSIC,
      self::MANUAL,
      self::PRO,
    ];
  }


  public function getValue(): string {
    return $this->value;
  }


  public function useAteForOldTranslationsCreatedWithCte(): bool {
    return $this->useAteForOldTranslationsCreatedWithCte;
  }


  public function setUseAteForOldTranslationsCreatedWithCte( bool $useAteForOldTranslationsCreatedWithCte ): self {
    $this->useAteForOldTranslationsCreatedWithCte = $useAteForOldTranslationsCreatedWithCte;

    return $this;
  }


  public static function createDefault(): self {
    return new self( self::ATE );
  }


}
