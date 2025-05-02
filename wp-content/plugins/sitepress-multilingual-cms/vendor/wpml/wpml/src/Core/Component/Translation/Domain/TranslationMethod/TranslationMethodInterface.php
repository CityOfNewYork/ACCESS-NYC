<?php

namespace WPML\Core\Component\Translation\Domain\TranslationMethod;

use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationMethod\TargetLanguageMethodType;

interface TranslationMethodInterface {


  /** @return TargetLanguageMethodType::* */
  public function get();


}
