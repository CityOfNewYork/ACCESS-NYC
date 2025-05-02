<?php

namespace WPML\Core\Component\Translation\Application\Service\Validator;

use WPML\Core\Component\Translation\Application\Service\Dto\SendToTranslationDto;
use WPML\Core\Component\Translation\Application\Service\Dto\TargetLanguageMethodDto;
use WPML\Core\Component\Translation\Application\Service\Validator\Dto\ValidationResultDto;
use WPML\Core\Component\Translation\Domain\TranslationMethod\TranslationServiceMethod;
use WPML\Core\Component\Translation\Domain\TranslationMethod\Validator\AssignedTranslationServiceValidator;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationMethod\TargetLanguageMethodType;
use WPML\Core\SharedKernel\Component\TranslationProxy\Domain\Query\FetchRemoteTranslationServiceException;

class AssignedTranslationServiceValidatorService implements ValidatorServiceInterface {

  /** @var AssignedTranslationServiceValidator */
  private $assignedTranslationServiceValidator;


  public function __construct( AssignedTranslationServiceValidator $assignedTranslationServiceValidator ) {
    $this->assignedTranslationServiceValidator = $assignedTranslationServiceValidator;
  }


  /**
   * @param SendToTranslationDto $sendToTranslationDto
   *
   * @return ValidationResultDto
   * @throws FetchRemoteTranslationServiceException
   */
  public function validate( SendToTranslationDto $sendToTranslationDto ): ValidationResultDto {
    $translationServiceMethods = $this->extractTranslationServiceMethods( $sendToTranslationDto );

    $result = $this->assignedTranslationServiceValidator->validate( $translationServiceMethods );

    return new ValidationResultDto( $this->getType(), $result );
  }


  public function getType(): string {
    return 'translation-service-validation';
  }


  /**
   * @param SendToTranslationDto $sendToTranslationDto
   *
   * @return TranslationServiceMethod[]
   */
  private function extractTranslationServiceMethods( SendToTranslationDto $sendToTranslationDto ): array {
    $translationServiceMethodsDto = array_filter(
      $sendToTranslationDto->getTargetLanguageMethods(),
      function ( TargetLanguageMethodDto $targetLanguageMethodDto ) {
        return $targetLanguageMethodDto->getTranslationMethod() === TargetLanguageMethodType::TRANSLATION_SERVICE;
      }
    );

    return array_map(
      function ( TargetLanguageMethodDto $translationServiceMethod ) {
        return new TranslationServiceMethod(
        // Validator will handle checking if the value is valid or not
          $translationServiceMethod->getTranslatorId() ?? 0
        );
      },
      $translationServiceMethodsDto
    );
  }


}
