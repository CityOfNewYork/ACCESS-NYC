<?php

namespace WPML\Core\Component\Translation\Domain\TranslationBatch\Validator;

use WPML\Core\Component\Translation\Domain\TranslationMethod\TranslationMethodInterface;
use WPML\Core\Component\Translation\Domain\TranslationType;

class IgnoredElement {

  /** @var TranslationType */
  private $translationType;

  /** @var int */
  private $elementId;

  /** @var string */
  private $targetLanguageCode;

  /** @var TranslationMethodInterface */
  private $translationMethod;

  /** @var string */
  private $reason;


  public function __construct(
    TranslationType $translationType,
    int $elementId,
    string $targetLanguageCode,
    TranslationMethodInterface $translationMethod,
    string $reason
  ) {
    $this->translationType    = $translationType;
    $this->elementId          = $elementId;
    $this->targetLanguageCode = $targetLanguageCode;
    $this->translationMethod  = $translationMethod;
    $this->reason             = $reason;
  }


  public function getTranslationType(): TranslationType {
    return $this->translationType;
  }


  public function getElementId(): int {
    return $this->elementId;
  }


  public function getTargetLanguageCode(): string {
    return $this->targetLanguageCode;
  }


  public function getTranslationMethod(): TranslationMethodInterface {
    return $this->translationMethod;
  }


  public function getReason(): string {
    return $this->reason;
  }


}
