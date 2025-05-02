<?php

namespace WPML\Core\Component\Translation\Application\Service\TranslationService\Dto;

/**
 * @phpstan-type CreatedTranslationDtoArray array{
 *   id: int,
 *   type: string,
 *   status: int,
 *   originalElementId: int,
 *   sourceLanguageCode: string,
 *   targetLanguageCode: string,
 *   translatedElementId: int|null,
 *   translationMethod: string|null,
 *   jobId: int|null
 * }
 */
class CreatedTranslationDto {

  /** @var int */
  private $id;

  /** @var string */
  private $type;

  /** @var int */
  private $status;

  /** @var int */
  private $originalElementId;

  /** @var string */
  private $sourceLanguageCode;

  /** @var string */
  private $targetLanguageCode;

  /** @var int|null */
  private $translatedElementId;

  /** @var string|null */
  private $translationMethod;

  /** @var int|null */
  private $jobId;


  /**
   * @param int         $id
   * @param string      $type
   * @param int         $status
   * @param int         $originalElementId
   * @param string      $sourceLanguageCode
   * @param string      $targetLanguageCode
   * @param string|null $translationMethod
   * @param int|null    $translatedElementId
   * @param int|null    $jobId
   *
   */
  public function __construct(
    int $id,
    string $type,
    int $status,
    int $originalElementId,
    string $sourceLanguageCode,
    string $targetLanguageCode,
    string $translationMethod = null,
    int $translatedElementId = null,
    int $jobId = null
  ) {
    $this->id                  = $id;
    $this->type                = $type;
    $this->status              = $status;
    $this->originalElementId   = $originalElementId;
    $this->sourceLanguageCode  = $sourceLanguageCode;
    $this->targetLanguageCode  = $targetLanguageCode;
    $this->translatedElementId = $translatedElementId;
    $this->translationMethod   = $translationMethod;
    $this->jobId               = $jobId;
  }


  public function getId(): int {
    return $this->id;
  }


  public function getType(): string {
    return $this->type;
  }


  public function getStatus(): int {
    return $this->status;
  }


  public function getOriginalElementId(): int {
    return $this->originalElementId;
  }


  public function getSourceLanguageCode(): string {
    return $this->sourceLanguageCode;
  }


  public function getTargetLanguageCode(): string {
    return $this->targetLanguageCode;
  }


  /**
   * @return int|null
   */
  public function getTranslatedElementId() {
    return $this->translatedElementId;
  }


  /**
   * @return string|null
   */
  public function getTranslationMethod() {
    return $this->translationMethod;
  }


  /**
   * @return int|null
   */
  public function getJobId() {
    return $this->jobId;
  }


  /**
   * @phpstan-return  CreatedTranslationDtoArray
   */
  public function toArray(): array {
    return [
      'id'                  => $this->id,
      'type'                => $this->type,
      'status'              => $this->status,
      'originalElementId'   => $this->originalElementId,
      'sourceLanguageCode'  => $this->sourceLanguageCode,
      'targetLanguageCode'  => $this->targetLanguageCode,
      'translatedElementId' => $this->translatedElementId,
      'translationMethod'   => $this->translationMethod,
      'jobId'               => $this->jobId
    ];
  }


}
