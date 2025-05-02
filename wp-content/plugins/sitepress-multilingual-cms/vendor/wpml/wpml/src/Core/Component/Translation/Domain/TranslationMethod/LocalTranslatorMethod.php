<?php

namespace WPML\Core\Component\Translation\Domain\TranslationMethod;

use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationMethod\TargetLanguageMethodType;

class LocalTranslatorMethod implements TranslationMethodInterface {

  /**
   * The value can be 0. It means that any available translator can take the job.
   * Such job also has WAITING FOR TRANSLATOR status.
   *
   * @var int
   */
  private $translatorId;

  /** @var string */
  private $targetLanguageCode;


  public function __construct( int $translatorId, string $targetLanguageCode ) {
    $this->translatorId       = $translatorId;
    $this->targetLanguageCode = $targetLanguageCode;
  }


  /** @return TargetLanguageMethodType::LOCAL_TRANSLATOR */
  public function get() {
    return TargetLanguageMethodType::LOCAL_TRANSLATOR;
  }


  public function getTranslatorId(): int {
    return $this->translatorId;
  }


  public function getTargetLanguageCode(): string {
    return $this->targetLanguageCode;
  }


}
