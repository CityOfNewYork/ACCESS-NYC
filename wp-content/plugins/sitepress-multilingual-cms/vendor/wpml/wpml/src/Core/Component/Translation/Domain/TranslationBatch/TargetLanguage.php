<?php

namespace WPML\Core\Component\Translation\Domain\TranslationBatch;

use WPML\Core\Component\Translation\Domain\TranslationMethod\TranslationMethodInterface;

final class TargetLanguage {

  /** @var string */
  private $languageCode;

  /** @var TranslationMethodInterface */
  private $method;

  /** @var Element[] */
  private $elements;


  /**
   * @param string                     $languageCode
   * @param TranslationMethodInterface $method
   * @param Element[]                  $elements
   */
  public function __construct( string $languageCode, TranslationMethodInterface $method, array $elements ) {
    $this->languageCode = $languageCode;
    $this->method       = $method;
    $this->elements     = $elements;
  }


  public function getLanguageCode(): string {
    return $this->languageCode;
  }


  public function getMethod(): TranslationMethodInterface {
    return $this->method;
  }


  /**
   * @return Element[]
   */
  public function getElements(): array {
    return $this->elements;
  }


}
