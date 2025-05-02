<?php

namespace WPML\Core\Component\Post\Application\Query\Dto;

use WPML\Core\SharedKernel\Component\Translation\Domain\ReviewStatus;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationEditorType;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationMethod\TargetLanguageMethodType;
use WPML\PHP\ConstructableFromArrayInterface;
use WPML\PHP\ConstructableFromArrayTrait;

/**
 * @implements ConstructableFromArrayInterface<TranslationStatusDto>
 */
final class TranslationStatusDto implements ConstructableFromArrayInterface {
  /** @use ConstructableFromArrayTrait<TranslationStatusDto> */
  use ConstructableFromArrayTrait;

  /** @var int */
  private $status;

  /** @var ?ReviewStatus::* */
  private $reviewStatus;

  /** @var int|null */
  private $jobId;

  /** @var ?TargetLanguageMethodType::* */
  private $method;

  /** @var TranslationEditorType::* */
  private $editor;

  /** @var bool */
  private $isTranslated;

  /** @var int|null */
  private $translatorId;


  /**
   * @param int                          $status
   * @param int|null                     $jobId
   * @param ?ReviewStatus::*             $reviewStatus
   * @param ?TargetLanguageMethodType::* $method
   * @param TranslationEditorType::*     $editor
   * @param bool                         $isTranslated
   * @param int|null                     $translatorId
   */
  public function __construct(
    int $status,
    $reviewStatus = null,
    int $jobId = null,
    $method = null,
    $editor = TranslationEditorType::NONE,
    bool $isTranslated = false,
    int $translatorId = null
  ) {
    $this->status       = $status;
    $this->reviewStatus = $reviewStatus;
    $this->jobId        = $jobId;
    $this->method       = $method;
    $this->editor       = $editor;
    $this->isTranslated = $isTranslated;
    $this->translatorId = $translatorId;
  }


  public function getStatus(): int {
    return $this->status;
  }


  /**
   * @return ?ReviewStatus::*
   */
  public function getReviewStatus() {
    return $this->reviewStatus;
  }


  /**
   * @return int|null
   */
  public function getJobId() {
    return $this->jobId;
  }


  /**
   * @return ?TargetLanguageMethodType::*
   */
  public function getMethod() {
    return $this->method;
  }


  /** @return TranslationEditorType::* */
  public function getEditor() {
    return $this->editor;
  }


  /**
   * @return array{
   *  status: int,
   *  reviewStatus: ?ReviewStatus::*,
   *  jobId: int|null,
   *  method: ?TargetLanguageMethodType::*,
   *  editor: TranslationEditorType::*
   *  }
   */
  public function toArray(): array {
    return [
      'status'       => $this->status,
      'reviewStatus' => $this->reviewStatus,
      'jobId'        => $this->jobId,
      'method'       => $this->method,
      'editor'       => $this->editor,
      'isTranslated' => $this->isTranslated,
      'translatorId' => $this->translatorId
    ];
  }


}
