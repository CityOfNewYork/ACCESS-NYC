<?php

namespace WPML\Core\Component\Post\Application\Query\Criteria;

final class SearchPopulatedTypesCriteria {

  /**
   * @var string[]
   */
  private $itemSectionIds = [];

  /** @var string|null */
  private $publicationStatus;

  /** @var SourceAndTargetLanguages */
  private $languages;

  /** @var int[] */
  private $translationStatuses = [];


  /**
   * @param SourceAndTargetLanguages $languages
   * @param array<string>            $itemSectionIds
   * @param string|null              $publicationStatus
   * @param array<int>               $translationStatuses
   */
  public function __construct(
    SourceAndTargetLanguages $languages,
    array $itemSectionIds = [],
    string $publicationStatus = null,
    array $translationStatuses = []
  ) {
    $this->languages           = $languages;
    $this->itemSectionIds      = $itemSectionIds;
    $this->publicationStatus   = $publicationStatus;
    $this->translationStatuses = $translationStatuses;
  }


  /** @return ?string */
  public function getPublicationStatus() {
    return $this->publicationStatus;
  }


  public function getSourceLanguageCode(): string {
    return $this->languages->getSourceLanguageCode();
  }


  /** @return string[] */
  public function getTargetLanguageCodes(): array {
    return $this->languages->getTargetLanguageCodes();
  }


  /** @return int[] */
  public function getTranslationStatuses() {
    return $this->translationStatuses;
  }


  /** @return string[] */
  public function getItemSectionIds() {
    return $this->itemSectionIds;
  }


  /**
   * @return string[]
   */
  public function getPostTypeIds(): array {
    return array_map(
      function ( $itemSectionId ) {
        return str_replace( 'post/', '', $itemSectionId );
      },
      array_filter(
        $this->itemSectionIds,
        function ( $itemSectionId ) {
          return strpos( $itemSectionId, 'post/' ) === 0;
        }
      )
    );
  }


}
