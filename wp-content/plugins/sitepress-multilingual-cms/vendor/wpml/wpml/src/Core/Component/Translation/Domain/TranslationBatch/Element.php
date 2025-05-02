<?php

namespace WPML\Core\Component\Translation\Domain\TranslationBatch;

use WPML\Core\Component\Translation\Domain\Translation;
use WPML\Core\Component\Translation\Domain\TranslationType;

class Element {

  /** @var int */
  private $elementId;

  /** @var TranslationType */
  private $type;

  /** @var string */
  private $originalLanguageCode;

  /** @var Translation[] */
  private $existingTranslations;


  /**
   * @param int             $elementId
   * @param TranslationType $type
   * @param string          $originalLanguageCode
   * @param Translation[]   $existingTranslation
   */
  public function __construct(
    int $elementId,
    TranslationType $type,
    string $originalLanguageCode,
    array $existingTranslation = []
  ) {
    $this->elementId            = $elementId;
    $this->type                 = $type;
    $this->originalLanguageCode = $originalLanguageCode;
    $this->existingTranslations = $existingTranslation;
  }


  public function getElementId(): int {
    return $this->elementId;
  }


  public function getType(): TranslationType {
    return $this->type;
  }


  public function getOriginalLanguageCode(): string {
    return $this->originalLanguageCode;
  }


  /**
   * @return Translation[]
   */
  public function getExistingTranslations(): array {
    return $this->existingTranslations;
  }


}
