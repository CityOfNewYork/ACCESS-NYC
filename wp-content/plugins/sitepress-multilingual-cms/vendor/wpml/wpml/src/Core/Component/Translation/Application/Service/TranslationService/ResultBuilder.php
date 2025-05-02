<?php

namespace WPML\Core\Component\Translation\Application\Service\TranslationService;

use WPML\Core\Component\Translation\Application\Service\TranslationService\Dto\CreatedTranslationDto;
use WPML\Core\Component\Translation\Application\Service\TranslationService\Dto\IgnoredElementDto;
use WPML\Core\Component\Translation\Application\Service\TranslationService\Dto\ResultDto;
use WPML\Core\Component\Translation\Domain\Translation;
use WPML\Core\Component\Translation\Domain\TranslationBatch\Validator\IgnoredElement;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationMethod\TargetLanguageMethodType;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationStatus;

class ResultBuilder {


  /**
   * @param Translation[]    $translations
   * @param IgnoredElement[] $ignoredElements
   *
   * @return ResultDto
   */
  public function build( array $translations, array $ignoredElements ): ResultDto {
    $createdTranslations = array_map(
      function ( Translation $translation ) {
        $method = null;

        $job = $translation->getJob();
        if ( $job ) {
          $method = $job->getTranslationMethod()->get();
        } else if ( $translation->getStatus()->get() === TranslationStatus::DUPLICATE ) {
          $method = TargetLanguageMethodType::DUPLICATE;
        }

        return new CreatedTranslationDto(
          $translation->getId(),
          $translation->getType()->get(),
          $translation->getStatus()->get(),
          $translation->getOriginalElementId(),
          $translation->getSourceLanguageCode(),
          $translation->getTargetLanguageCode(),
          $method,
          $translation->getTranslatedElementId(),
          $job ? $job->getId() : null
        );
      },
      $translations
    );

    $ignoredElements = array_map(
      function ( IgnoredElement $ignoredElement ) {
        return new IgnoredElementDto(
          $ignoredElement->getElementId(),
          $ignoredElement->getTranslationType()->get(),
          $ignoredElement->getTargetLanguageCode(),
          $ignoredElement->getReason()
        );
      },
      $ignoredElements
    );

    return new ResultDto(
      $createdTranslations,
      $ignoredElements
    );
  }


}
