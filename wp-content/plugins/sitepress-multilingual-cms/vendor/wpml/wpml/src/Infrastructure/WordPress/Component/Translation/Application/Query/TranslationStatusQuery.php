<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Query;

use WPML\Core\Component\Translation\Application\Query\Dto\TranslationStatusDto;
use WPML\Core\Component\Translation\Application\Query\TranslationQueryInterface;
use WPML\Core\Component\Translation\Application\Query\TranslationStatusQueryInterface;
use WPML\Core\Component\Translation\Application\String\StringBatchToStringsTranslationsMapper;
use WPML\Core\Component\Translation\Domain\Translation;

class TranslationStatusQuery implements TranslationStatusQueryInterface {

  /** @var TranslationQueryInterface */
  private $translationQuery;

  /** @var StringBatchToStringsTranslationsMapper */
  private $stringBatchToStringsTranslationMapper;


  public function __construct(
    TranslationQueryInterface $translationQuery,
    StringBatchToStringsTranslationsMapper $stringBatchToStringsTranslationMapper
  ) {
    $this->translationQuery                      = $translationQuery;
    $this->stringBatchToStringsTranslationMapper = $stringBatchToStringsTranslationMapper;
  }


  /**
   * @param int[] $jobIds
   * @param bool  $mapStringBatchesOnIndividualStrings Normally we return a TranslationStatusDto
   *    for each string batch. If this is set to true,
   *    we return a TranslationStatusDto for each string included in the batch.
   *
   * @return TranslationStatusDto[]
   */
  public function getByJobIds( array $jobIds, bool $mapStringBatchesOnIndividualStrings = false ): array {
    $translations = $this->translationQuery->getManyByJobIds( $jobIds );
    if ( $mapStringBatchesOnIndividualStrings ) {
      $translations = $this->stringBatchToStringsTranslationMapper->map( $translations );
    }

    return array_map(
      function ( Translation $translation ) {
        $reviewStatus = $translation->getReviewStatus();

        return new TranslationStatusDto(
          $translation->getOriginalElementId(),
          $translation->getType()->get(),
          $translation->getTargetLanguageCode(),
          $translation->getStatus()->get(),
          $reviewStatus ? $reviewStatus->getValue() : null
        );
      },
      $translations
    );
  }


}
