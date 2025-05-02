<?php

// phpcs:ignore PHPCompatibility.Keywords.ForbiddenNamesAsDeclared.stringFound
namespace WPML\Core\Component\Translation\Application\String;

use WPML\Core\Component\Translation\Application\String\Query\StringsFromBatchQueryInterface;
use WPML\Core\Component\Translation\Domain\Translation;
use WPML\Core\Component\Translation\Domain\TranslationType;
use function WPML\PHP\flatten;
use function WPML\PHP\partition;

class StringBatchToStringsTranslationsMapper {

  /** @var StringsFromBatchQueryInterface */
  private $stringsFromBatchQuery;


  public function __construct( StringsFromBatchQueryInterface $stringsFromBatchQuery ) {
    $this->stringsFromBatchQuery = $stringsFromBatchQuery;
  }


  /**
   * @param Translation[] $translations
   *
   * @return Translation[]
   */
  public function map( array $translations ): array {
    list( $stringBatchTranslations, $otherTranslations ) = partition(
      $translations,
      function ( $translation ) {
        return $translation->getType()->get() === TranslationType::STRING_BATCH;
      }
    );

    $stringTranslations     = array_map(
      function ( $stringBatchTranslation ) {
        return $this->mapStringBatchTranslationToStringsTranslations( $stringBatchTranslation );
      },
      $stringBatchTranslations
    );
    $stringBatchTranslation = flatten( $stringTranslations );

    return array_merge( $otherTranslations, $stringBatchTranslation );
  }


  /**
   * @param Translation $translation
   *
   * @return Translation[]
   */
  private function mapStringBatchTranslationToStringsTranslations( Translation $translation ): array {
    $stringIds = $this->stringsFromBatchQuery->get( $translation->getOriginalElementId() );

    return array_map(
      function ( $stringId ) use ( $translation ) {
        return new Translation(
          $translation->getId(),
          $translation->getStatus(),
          TranslationType::string(),
          $stringId,
          $translation->getSourceLanguageCode(),
          $translation->getTargetLanguageCode(),
          $translation->getJob(),
          $translation->getTranslatedElementId(),
          $translation->getReviewStatus()
        );
      },
      $stringIds
    );
  }


}
