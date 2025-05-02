<?php

namespace WPML\Core\Component\Translation\Domain\TranslationBatch\Validator;

use WPML\Core\Component\Translation\Domain\HowToHandleExistingTranslationType;
use WPML\Core\Component\Translation\Domain\TranslationBatch\Element;
use WPML\Core\Component\Translation\Domain\TranslationBatch\TargetLanguage;
use WPML\Core\Component\Translation\Domain\TranslationBatch\TranslationBatch;
use WPML\Core\Component\Translation\Domain\TranslationMethod\AutomaticMethod;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationStatus;


class CompletedTranslationValidator implements ValidatorInterface {

  const IGNORED_ELEMENT_REASON = 'content_already_translated';


  /**
   * We shall ignore already translated element if
   *  - the chosen translation method is automatic
   *  - a user chose not to override existing translations
   *
   * @param TranslationBatch $translationBatch
   *
   * @return array{0: TranslationBatch, 1: IgnoredElement[]}
   */
  public function validate( TranslationBatch $translationBatch ): array {
    if (
      $translationBatch->getHowToHandleExisting() ===
      HowToHandleExistingTranslationType::HANDLE_EXISTING_OVERRIDE
    ) {
      return [ $translationBatch, [] ];
    }

    $ignoredElements = [];
    $targetLanguages = [];
    foreach ( $translationBatch->getTargetLanguages() as $targetLanguage ) {
      if ( $targetLanguage->getMethod() instanceof AutomaticMethod ) {
        $correctElements = [];

        foreach ( $targetLanguage->getElements() as $element ) {
          if ( $this->hasCompletedTranslationInGivenLanguage( $element, $targetLanguage->getLanguageCode() ) ) {
            $ignoredElements[] = new IgnoredElement(
              $element->getType(),
              $element->getElementId(),
              $targetLanguage->getLanguageCode(),
              $targetLanguage->getMethod(),
              self::IGNORED_ELEMENT_REASON
            );
          } else {
            $correctElements[] = $element;
          }
        }

        $targetLanguages[] = new TargetLanguage(
          $targetLanguage->getLanguageCode(),
          $targetLanguage->getMethod(),
          $correctElements
        );
      } else {
        $targetLanguages[] = $targetLanguage;
      }
    }

    $translationBatch = $translationBatch->copyWithNewTargetLanguages( $targetLanguages );

    return [ $translationBatch, $ignoredElements ];
  }


  private function hasCompletedTranslationInGivenLanguage( Element $element, string $languageCode ): bool {
    $existingTranslations = $element->getExistingTranslations();

    foreach ( $existingTranslations as $translation ) {
      if (
        $translation->getTargetLanguageCode() === $languageCode &&
        $translation->getStatus()->get() === TranslationStatus::COMPLETE &&
        ! $translation->needsUpdate()
      ) {
        return true;
      }
    }

    return false;
  }


}
