<?php

namespace WPML\Core\Component\Translation\Domain\TranslationMethod;

use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationMethod\TargetLanguageMethodType;

class DuplicateMethod implements TranslationMethodInterface {


  /** @return TargetLanguageMethodType::DUPLICATE */
  public function get() {
    return TargetLanguageMethodType::DUPLICATE;
  }


}
