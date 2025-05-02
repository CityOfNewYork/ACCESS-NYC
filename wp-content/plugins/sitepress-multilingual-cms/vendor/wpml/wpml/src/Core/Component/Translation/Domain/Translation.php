<?php

namespace WPML\Core\Component\Translation\Domain;

use WPML\Core\SharedKernel\Component\Translation\Domain\ReviewStatus;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationStatus;

class Translation {

  /** @var int */
  private $id;

  /** @var TranslationStatus */
  private $status;

  /** @var ReviewStatus|null */
  private $reviewStatus;

  /** @var TranslationType */
  private $type;

  /** @var Job|null */
  private $job;

  /** @var int */
  private $originalElementId;

  /** @var int|null */
  private $translatedElementId;

  /** @var string */
  private $sourceLanguageCode;

  /** @var string */
  private $targetLanguageCode;

  /** @var bool */
  private $needsUpdate;


  public function __construct(
    int $id,
    TranslationStatus $status,
    TranslationType $type,
    int $originalElementId,
    string $sourceLanguageCode,
    string $targetLanguageCode,
    Job $job = null,
    int $translatedElementId = null,
    ReviewStatus $reviewStatus = null,
    bool $needsUpdate = false
  ) {
    $this->id                  = $id;
    $this->status              = $status;
    $this->type                = $type;
    $this->originalElementId   = $originalElementId;
    $this->sourceLanguageCode  = $sourceLanguageCode;
    $this->targetLanguageCode  = $targetLanguageCode;
    $this->translatedElementId = $translatedElementId;
    $this->job                 = $job;
    $this->reviewStatus        = $reviewStatus;
    $this->needsUpdate         = $needsUpdate;
  }


  public function getId(): int {
    return $this->id;
  }


  public function getStatus(): TranslationStatus {
    return $this->status;
  }


  public function getType(): TranslationType {
    return $this->type;
  }


  /**
   * @return Job|null
   */
  public function getJob() {
    return $this->job;
  }


  public function getOriginalElementId(): int {
    return $this->originalElementId;
  }


  /**
   * @return int|null
   */
  public function getTranslatedElementId() {
    return $this->translatedElementId;
  }


  public function getSourceLanguageCode(): string {
    return $this->sourceLanguageCode;
  }


  public function getTargetLanguageCode(): string {
    return $this->targetLanguageCode;
  }


  /**
   * @return ReviewStatus|null
   */
  public function getReviewStatus() {
    return $this->reviewStatus;
  }


  public function needsUpdate(): bool {
    return $this->needsUpdate;
  }


}
