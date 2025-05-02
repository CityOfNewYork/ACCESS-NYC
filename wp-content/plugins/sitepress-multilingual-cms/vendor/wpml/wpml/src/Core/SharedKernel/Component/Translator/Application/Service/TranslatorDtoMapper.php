<?php

namespace WPML\Core\SharedKernel\Component\Translator\Application\Service;

use WPML\Core\SharedKernel\Component\Translator\Application\Service\Dto\LanguagePairDto;
use WPML\Core\SharedKernel\Component\Translator\Application\Service\Dto\TranslatorDto;
use WPML\Core\SharedKernel\Component\Translator\Domain\Translator;

class TranslatorDtoMapper {


  public static function map( Translator $translator ): TranslatorDto {
    $translatorLanguagePairsDto = array_map(
      function ( $languagePair ) {
        return new LanguagePairDto( $languagePair->getFrom(), $languagePair->getTo() );
      },
      $translator->getLanguagePairs()
    );

    return new TranslatorDto(
      $translator->getId(),
      $translator->getName(),
      $translator->getUserName(),
      $translatorLanguagePairsDto
    );
  }


}
