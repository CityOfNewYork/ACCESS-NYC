<?php

namespace WPML\Core\SharedKernel\Component\Translation\Domain\TranslationMethod;

/**
 * @phpstan-type TargetLanguageMethodTypeValues self::TRANSLATION_SERVICE | self::LOCAL_TRANSLATOR | self::AUTOMATIC | self::DUPLICATE
 */
class TargetLanguageMethodType {
  const TRANSLATION_SERVICE = 'translation-service';
  const LOCAL_TRANSLATOR = 'local-translator';
  const AUTOMATIC = 'automatic';
  const DUPLICATE = 'duplicate';
}
