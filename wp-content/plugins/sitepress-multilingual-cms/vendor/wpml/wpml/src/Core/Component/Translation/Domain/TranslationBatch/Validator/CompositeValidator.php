<?php

namespace WPML\Core\Component\Translation\Domain\TranslationBatch\Validator;

use WPML\Core\Component\Translation\Domain\TranslationBatch\TranslationBatch;

class CompositeValidator implements ValidatorInterface {

  /** @var ValidatorInterface[] */
  private $validators;


  /**
   * It is essential to have EmptyMethodsValidator as the last validator in the chain.
   * See its description for more details.
   *
   * @param ValidatorInterface[]  $validators
   * @param EmptyMethodsValidator $emptyMethodsValidator
   */
  public function __construct( array $validators, EmptyMethodsValidator $emptyMethodsValidator ) {
    $this->validators   = $validators;
    $this->validators[] = $emptyMethodsValidator;
  }


  /**
   * @param TranslationBatch $translationBatch
   *
   * @return array{0: TranslationBatch|null, 1: IgnoredElement[]}
   */
  public function validate( TranslationBatch $translationBatch ): array {
    $ignoredElements = [];

    foreach ( $this->validators as $validator ) {
      list( $translationBatch, $newIgnoredElements ) = $validator->validate( $translationBatch );

      // It may happen that the whole batch is invalid and we should stop processing it.
      // It does not make sense to run further validations.
      if ( ! $translationBatch ) {
        break;
      }

      $ignoredElements = array_merge( $ignoredElements, $newIgnoredElements );
    }

    return [ $translationBatch, $ignoredElements ];
  }


}
