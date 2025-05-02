<?php

namespace WPML\Core\Component\Translation\Domain\TranslationBatch\Validator;

use WPML\Core\Component\Translation\Domain\TranslationBatch\TargetLanguage;
use WPML\Core\Component\Translation\Domain\TranslationBatch\TranslationBatch;

class ElementTargetLanguageValidator implements ValidatorInterface {

  const IGNORED_ELEMENT_REASON = 'invalid_source_language';


  /**
   * Check if the target language of given elements is not their actual source language.
   *
   * @param TranslationBatch $translationBatch
   *
   * @return array{0: TranslationBatch, 1: IgnoredElement[]}
   */
  public function validate( TranslationBatch $translationBatch ): array {
    $ignoredElements = [];
    $targetLanguages = [];

    foreach ( $translationBatch->getTargetLanguages() as $targetLanguage ) {
      $validElements = [];

      foreach ( $targetLanguage->getElements() as $element ) {
        if ( $element->getOriginalLanguageCode() === $targetLanguage->getLanguageCode() ) {
          $ignoredElements[] = new IgnoredElement(
            $element->getType(),
            $element->getElementId(),
            $targetLanguage->getLanguageCode(),
            $targetLanguage->getMethod(),
            self::IGNORED_ELEMENT_REASON
          );
        } else {
          $validElements[] = $element;
        }
      }

      if ( ! empty( $validElements ) ) {
        $targetLanguages[] = new TargetLanguage(
          $targetLanguage->getLanguageCode(),
          $targetLanguage->getMethod(),
          $validElements
        );
      }
    }

    $translationBatch = $translationBatch->copyWithNewTargetLanguages( $targetLanguages );

    return [ $translationBatch, $ignoredElements ];
  }


}
