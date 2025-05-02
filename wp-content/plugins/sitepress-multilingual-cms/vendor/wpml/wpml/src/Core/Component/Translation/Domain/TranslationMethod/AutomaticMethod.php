<?php

namespace WPML\Core\Component\Translation\Domain\TranslationMethod;

use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationMethod\TargetLanguageMethodType;

class AutomaticMethod implements TranslationMethodInterface {


  /** @return TargetLanguageMethodType::AUTOMATIC */
  public function get() {
    return TargetLanguageMethodType::AUTOMATIC;
  }


}
