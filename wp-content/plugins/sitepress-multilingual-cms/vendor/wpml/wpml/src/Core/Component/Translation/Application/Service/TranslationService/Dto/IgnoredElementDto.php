<?php

namespace WPML\Core\Component\Translation\Application\Service\TranslationService\Dto;

/**
 * @phpstan-type IgnoredElementDtoArray array{
 *   elementId: int,
 *   elementType: string,
 *   targetLanguageCode: string,
 *   reason: string
 * }
 */
class IgnoredElementDto {

  /** @var int */
  private $elementId;

  /** @var string */
  private $elementType;

  /** @var string */
  private $targetLanguageCode;

  /** @var string */
  private $reason;


  public function __construct(
    int $elementId,
    string $elementType,
    string $targetLanguageCode,
    string $reason
  ) {
    $this->elementId          = $elementId;
    $this->elementType        = $elementType;
    $this->targetLanguageCode = $targetLanguageCode;
    $this->reason             = $reason;
  }


  public function getElementId(): int {
    return $this->elementId;
  }


  public function getElementType(): string {
    return $this->elementType;
  }


  public function getTargetLanguageCode(): string {
    return $this->targetLanguageCode;
  }


  public function getReason(): string {
    return $this->reason;
  }


  /**
   * @phpstan-return IgnoredElementDtoArray
   */
  public function toArray(): array {
    return [
      'elementId'          => $this->elementId,
      'elementType'        => $this->elementType,
      'targetLanguageCode' => $this->targetLanguageCode,
      'reason'             => $this->reason,
    ];
  }


}
