<?php

namespace WPML\Core\Component\Translation\Domain\TranslationMethod;

use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationMethod\TargetLanguageMethodType;

class TranslationServiceMethod implements TranslationMethodInterface {

  /** @var int */
  private $serviceId;


  public function __construct( int $serviceId ) {
    $this->serviceId = $serviceId;
  }


  /** @return TargetLanguageMethodType::TRANSLATION_SERVICE */
  public function get() {
    return TargetLanguageMethodType::TRANSLATION_SERVICE;
  }


  public function getServiceId(): int {
    return $this->serviceId;
  }


}
