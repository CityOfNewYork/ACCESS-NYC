<?php

namespace WPML\Core\Component\Translation\Domain\TranslationBatch\Validator;

use WPML\Core\Component\Translation\Domain\TranslationBatch\TranslationBatch;

interface ValidatorInterface {


  /**
   * A validator can return a null translation batch if entire batch is invalid, which means even a single
   * language method with given elements is not valid.
   *
   * @param TranslationBatch $translationBatch
   *
   * @return array{0: TranslationBatch|null, 1: IgnoredElement[]}
   */
  public function validate( TranslationBatch $translationBatch ): array;


}
