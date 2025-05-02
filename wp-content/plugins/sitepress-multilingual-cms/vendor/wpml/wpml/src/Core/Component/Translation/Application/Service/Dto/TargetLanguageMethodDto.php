<?php

namespace WPML\Core\Component\Translation\Application\Service\Dto;

use WPML\PHP\ConstructableFromArrayInterface;
use WPML\PHP\ConstructableFromArrayTrait;
use WPML\PHP\Exception\InvalidArgumentException;

/**
 * @implements ConstructableFromArrayInterface<TargetLanguageMethodDto>
 *
 * @phpstan-import-type TranslationServiceExtraFieldsArray from SendToTranslationExtraInformationDto
 *
 */
final class TargetLanguageMethodDto implements ConstructableFromArrayInterface {
  /** @use ConstructableFromArrayTrait<TargetLanguageMethodDto> */
  use ConstructableFromArrayTrait;

  /** @var string */
  private $targetLanguageCode;

  /** @var string */
  private $translationMethod;

  /** @var int|null */
  private $translatorId;


  /**
   * @param string   $targetLanguageCode
   * @param string   $translationMethod
   * @param int|null $translatorId
   *
   * @throws InvalidArgumentException
   */
  public function __construct(
    string $targetLanguageCode,
    string $translationMethod,
    int $translatorId = null
  ) {
    $this->targetLanguageCode = $targetLanguageCode;
    $this->translationMethod  = $translationMethod;
    $this->translatorId       = $translatorId;
  }


  public function getTargetLanguageCode(): string {
    return $this->targetLanguageCode;
  }


  public function getTranslationMethod(): string {
    return $this->translationMethod;
  }


  /**
   * @return int|null
   */
  public function getTranslatorId() {
    return $this->translatorId;
  }


}
