<?php

namespace WPML\Core\Component\Translation\Domain\TranslationBatch;

use WPML\Core\Component\Translation\Domain\HowToHandleExistingTranslationType;
use WPML\PHP\DateTime;

class TranslationBatch {

  /** @var string */
  private $batchName;

  /** @var DateTime|null */
  private $deadline;

  /** @var string */
  private $sourceLanguageCode;

  /** @var TargetLanguage[] */
  private $targetLanguages;

  /** @var string */
  private $howToHandleExisting;

  /** @var array<int, array<string, string>> | null */
  private $translationServiceExtraFields;


  /**
   * @param string                                   $batchName
   * @param string                                   $sourceLanguageCode
   * @param TargetLanguage[]                         $targetLanguages
   * @param string                                   $howToHandleExisting
   * @param array<int, array<string, string>> | null $translationServiceExtraFields
   * @param DateTime|null                            $deadline
   */
  public function __construct(
    string $batchName,
    string $sourceLanguageCode,
    array $targetLanguages,
    string $howToHandleExisting = HowToHandleExistingTranslationType::HANDLE_EXISTING_LEAVE,
    array $translationServiceExtraFields = null,
    DateTime $deadline = null
  ) {
    $this->batchName                     = $batchName;
    $this->sourceLanguageCode            = $sourceLanguageCode;
    $this->targetLanguages               = $targetLanguages;
    $this->howToHandleExisting           = $howToHandleExisting;
    $this->translationServiceExtraFields = $translationServiceExtraFields;
    $this->deadline                      = $deadline;
  }


  public function getBatchName(): string {
    return $this->batchName;
  }


  /**
   * @return DateTime|null
   */
  public function getDeadline() {
    return $this->deadline;
  }


  public function getSourceLanguageCode(): string {
    return $this->sourceLanguageCode;
  }


  /**
   * @return TargetLanguage[]
   */
  public function getTargetLanguages(): array {
    return $this->targetLanguages;
  }


  public function getHowToHandleExisting(): string {
    return $this->howToHandleExisting;
  }


  /**
   * @return array<int, array<string, string>> | null
   */
  public function getTranslationServiceExtraFields() {
    return $this->translationServiceExtraFields;
  }


  /**
   * @param TargetLanguage[] $targetLanguages
   *
   * @return TranslationBatch
   */
  public function copyWithNewTargetLanguages( array $targetLanguages ): self {
    return new self(
      $this->batchName,
      $this->sourceLanguageCode,
      $targetLanguages,
      $this->howToHandleExisting,
      $this->translationServiceExtraFields,
      $this->deadline
    );
  }


}
