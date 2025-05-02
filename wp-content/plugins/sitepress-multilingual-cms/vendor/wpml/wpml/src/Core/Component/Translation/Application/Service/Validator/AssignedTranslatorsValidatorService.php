<?php

namespace WPML\Core\Component\Translation\Application\Service\Validator;

use WPML\Core\Component\Translation\Application\Service\Dto\SendToTranslationDto;
use WPML\Core\Component\Translation\Application\Service\Dto\TargetLanguageMethodDto;
use WPML\Core\Component\Translation\Application\Service\Validator\Dto\ValidationResultDto;
use WPML\Core\Component\Translation\Domain\TranslationMethod\LocalTranslatorMethod;
use WPML\Core\Component\Translation\Domain\TranslationMethod\Validator\AssignedTranslatorsValidator;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationMethod\TargetLanguageMethodType;

class AssignedTranslatorsValidatorService implements ValidatorServiceInterface {

  /** @var AssignedTranslatorsValidator */
  private $assignedTranslatorsValidator;


  public function __construct( AssignedTranslatorsValidator $assignedTranslatorsValidator ) {
    $this->assignedTranslatorsValidator = $assignedTranslatorsValidator;
  }


  public function validate( SendToTranslationDto $sendToTranslationDto ): ValidationResultDto {
    $localTranslatorsMethods = $this->extractLocalTranslatorsMethods( $sendToTranslationDto );

    $result = $this->assignedTranslatorsValidator->validate(
      $localTranslatorsMethods,
      $sendToTranslationDto->getSourceLanguageCode()
    );

    return new ValidationResultDto( $this->getType(), $result );
  }


  public function getType(): string {
    return 'local-translators-validation';
  }


  /**
   * @param SendToTranslationDto $sendToTranslationDto
   *
   * @return LocalTranslatorMethod[]
   */
  private function extractLocalTranslatorsMethods( SendToTranslationDto $sendToTranslationDto ): array {
    $localTranslatorsMethodsDto = array_filter(
      $sendToTranslationDto->getTargetLanguageMethods(),
      function ( TargetLanguageMethodDto $targetLanguageMethodDto ) {
        return $targetLanguageMethodDto->getTranslationMethod() === TargetLanguageMethodType::LOCAL_TRANSLATOR;
      }
    );

    return array_map(
      function ( TargetLanguageMethodDto $localTranslatorMethod ) {
        return new LocalTranslatorMethod(
          $localTranslatorMethod->getTranslatorId() ?? 0,
          $localTranslatorMethod->getTargetLanguageCode()
        );
      },
      $localTranslatorsMethodsDto
    );
  }


}
