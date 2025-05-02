<?php

namespace WPML\Core\Component\Post\Application\Query\Criteria;

use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;
use WPML\PHP\Exception\InvalidArgumentException;

final class SearchPopulatedTypesCriteriaBuilder {

  /** @var SourceAndTargetLanguagesBuilder */
  private $languagesBuilder;


  public function __construct( LanguagesQueryInterface $languagesQuery ) {
    $this->languagesBuilder = new SourceAndTargetLanguagesBuilder( $languagesQuery );
  }


  /**
   * @param array{
   *   sourceLanguageCode?: string|null,
   *   targetLanguageCode?: string|null,
   *   itemSectionIds?: array<string>,
   *   publicationStatus?: string|null,
   *   translationStatuses?: array<int>
   * } $array
   *
   * @return SearchPopulatedTypesCriteria
   * @throws InvalidArgumentException If a required argument is missing.
   *
   */
  public function build( array $array ): SearchPopulatedTypesCriteria {
    // Handle languages separately
    $languages = $this->languagesBuilder->build(
      $array['sourceLanguageCode'] ?? null,
      isset( $array['targetLanguageCode'] ) ? [ $array['targetLanguageCode'] ] : null
    );

    return new SearchPopulatedTypesCriteria(
      $languages,
      $array['itemSectionIds'] ?? [],
      $array['publicationStatus'] ?? null,
      $array['translationStatuses'] ?? []
    );
  }


}
