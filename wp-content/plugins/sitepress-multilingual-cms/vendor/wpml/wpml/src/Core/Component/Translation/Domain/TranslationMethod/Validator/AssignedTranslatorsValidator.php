<?php

namespace WPML\Core\Component\Translation\Domain\TranslationMethod\Validator;

use WPML\Core\Component\Translation\Domain\TranslationMethod\LocalTranslatorMethod;
use WPML\Core\SharedKernel\Component\Translator\Domain\Query\TranslatorsQueryInterface;
use WPML\Core\SharedKernel\Component\Translator\Domain\Translator;

class AssignedTranslatorsValidator {

  /** @var TranslatorsQueryInterface */
  private $translatorsQuery;

  /** @var array<int, Translator> */
  private $alreadyFetchedTranslators = [];


  public function __construct( TranslatorsQueryInterface $translatorsQuery ) {
    $this->translatorsQuery = $translatorsQuery;
  }


  /**
   * @param LocalTranslatorMethod[] $translationMethods
   * @param string $sourceLanguageCode
   *
   * @return bool
   */
  public function validate( array $translationMethods, string $sourceLanguageCode ): bool {
    // Return true if $translationMethods is empty because next check we are
    // asserting that $sourceLanguageCode is passed with truthy value, and we need
    // to do this checking only if we have $translationMethods.
    if ( ! count( $translationMethods ) ) {
      return true;
    }

    foreach ( $translationMethods as $translatorMethod ) {
      // Skip the case when FirstAvailable translator is selected
      // $translatorMethod->getTranslatorId() = 0
      if ( ! $translatorMethod->getTranslatorId() ) {
        continue;
      }

      // Check if we already fetched information about translator before
      if ( in_array( $translatorMethod->getTranslatorId(), array_keys( $this->alreadyFetchedTranslators ) ) ) {
        $translator = $this->alreadyFetchedTranslators[ $translatorMethod->getTranslatorId() ];
      } else {
        // Fetch the translator information if it's not saved in alreadyFetchedTranslators
        $translator = $this->translatorsQuery->getById( $translatorMethod->getTranslatorId() );
      }

      if ( ! $translator ) {
        // If assigned translator couldn't be fetched from DB,
        // return validation result immediately.
        return false;
      }

      // Save the translator information if it's not NULL
      $this->alreadyFetchedTranslators[ $translatorMethod->getTranslatorId() ] = $translator;

      if ( ! $this->isAssignedTranslatorStillEligible(
        $translator,
        $sourceLanguageCode,
        $translatorMethod->getTargetLanguageCode()
      ) ) {
        // If assigned translator can't still handle the target language assigned to him,
        // return validation result immediately.
        return false;
      }
    }

    return true;
  }


  private function isAssignedTranslatorStillEligible(
    Translator $translator,
    string $sourceLanguageCode,
    string $targetLanguageCode
  ): bool {

    $translatorLanguagePairs = $translator->toArray()['languagePairs'];

    $languagePairsOfSourceLanguageIndex = array_search(
      $sourceLanguageCode,
      array_column(
        $translatorLanguagePairs,
        'from'
      )
    );

    // means that source language don't exist in translator pairs anymore
    if ( $languagePairsOfSourceLanguageIndex === false ) {
      return false;
    }

    return in_array(
      $targetLanguageCode,
      $translatorLanguagePairs[ $languagePairsOfSourceLanguageIndex ]['to']
    );
  }


}
