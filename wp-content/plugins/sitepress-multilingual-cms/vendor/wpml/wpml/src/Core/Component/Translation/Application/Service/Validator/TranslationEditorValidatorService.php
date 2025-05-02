<?php

namespace WPML\Core\Component\Translation\Application\Service\Validator;

use WPML\Core\Component\Translation\Application\Repository\SettingsRepository;
use WPML\Core\Component\Translation\Application\Service\Dto\SendToTranslationDto;
use WPML\Core\Component\Translation\Application\Service\Dto\TargetLanguageMethodDto;
use WPML\Core\Component\Translation\Application\Service\Validator\Dto\ValidationResultDto;
use WPML\Core\Component\Translation\Domain\TranslationMethod\AutomaticMethod;
use WPML\Core\Component\Translation\Domain\TranslationMethod\Validator\TranslationEditorTypeValidator;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationMethod\TargetLanguageMethodType;

class TranslationEditorValidatorService implements ValidatorServiceInterface {

  /** @var SettingsRepository */
  private $settingsRepository;


  public function __construct( SettingsRepository $settingsRepository ) {
    $this->settingsRepository = $settingsRepository;
  }


  public function validate( SendToTranslationDto $sendToTranslationDto ): ValidationResultDto {
    $translationEditorSetting   = $this->settingsRepository->getSettings()->getTranslationEditor();
    $translationEditorValidator = new TranslationEditorTypeValidator( $translationEditorSetting );

    $automaticMethods = $this->extractAutomaticMethods( $sendToTranslationDto );

    $result = $translationEditorValidator->validate( $automaticMethods );

    return new ValidationResultDto( $this->getType(), $result );
  }


  public function getType(): string {
    return 'translation-editor-validation';
  }


  /**
   * @param SendToTranslationDto $sendToTranslationDto
   *
   * @return AutomaticMethod[]
   */
  public function extractAutomaticMethods( SendToTranslationDto $sendToTranslationDto ): array {
    $automaticMethods = array_filter(
      $sendToTranslationDto->getTargetLanguageMethods(),
      function ( TargetLanguageMethodDto $targetLanguageMethodDto ) {
        return $targetLanguageMethodDto->getTranslationMethod() === TargetLanguageMethodType::AUTOMATIC;
      }
    );

    return array_map(
      function ( TargetLanguageMethodDto $automaticMethod ) {
        return new AutomaticMethod();
      },
      $automaticMethods
    );
  }


}
