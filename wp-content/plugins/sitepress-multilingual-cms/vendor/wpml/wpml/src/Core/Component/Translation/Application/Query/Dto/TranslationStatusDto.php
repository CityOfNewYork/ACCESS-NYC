<?php

namespace WPML\Core\Component\Translation\Application\Query\Dto;

class TranslationStatusDto {

  /** @var int */
  private $itemId;

  /** @var string */
  private $type;

  /** @var string */
  private $targetLanguage;

  /** @var int */
  private $status;

  /** @var string|null */
  private $reviewStatus;


  public function __construct(
    int $itemId,
    string $type,
    string $targetLanguage,
    int $status,
    string $reviewStatus = null
  ) {
    $this->itemId         = $itemId;
    $this->type           = $type;
    $this->targetLanguage = $targetLanguage;
    $this->status         = $status;
    $this->reviewStatus   = $reviewStatus;

  }


  public function getItemId(): int {
    return $this->itemId;
  }


  public function getType(): string {
    return $this->type;
  }


  public function getTargetLanguage(): string {
    return $this->targetLanguage;
  }


  public function getStatus(): int {
    return $this->status;
  }


  /**
   * @return string|null
   */
  public function getReviewStatus() {
    return $this->reviewStatus;
  }


  /**
   * @return array{itemId: int, type: string, targetLanguage: string, status: int, reviewStatus: string|null}
   */
  public function toArray(): array {
    return [
      'itemId'         => $this->itemId,
      'type'           => $this->type,
      'targetLanguage' => $this->targetLanguage,
      'status'         => $this->status,
      'reviewStatus'   => $this->reviewStatus,
    ];
  }


}
