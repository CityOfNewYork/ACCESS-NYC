<?php

namespace WPML\Core\Component\Post\Application\Query\Criteria;

final class SearchCriteria {

  /** @var string */
  private $type;

  /** @var ?string */
  private $title;

  /** @var ?string */
  private $publicationStatus;

  /** @var SourceAndTargetLanguages */
  private $languages;

  /** @var int[] */
  private $translationStatuses;

  /** @var ?int */
  private $parentId;

  /** @var ?string */
  private $taxonomyId;

  /** @var ?int */
  private $termId;

  /** @var int */
  private $limit = 10;

  /** @var int */
  private $offset = 0;

  /** @var SortingCriteria | null */
  private $sortingCriteria;


  /**
   * @param string                                $type
   * @param string|null                           $title
   * @param string|null                           $publicationStatus
   * @param SourceAndTargetLanguages              $languages
   * @param array<int>                            $translationStatuses
   * @param int|null                              $parentId
   * @param string|null                           $taxonomyId
   * @param int|null                              $termId
   * @param int                                   $limit
   * @param int                                   $offset
   * @param array{by: string, order: string}|null $sorting
   */
  public function __construct(
    string $type,
    string $title = null,
    string $publicationStatus = null,
    SourceAndTargetLanguages $languages,
    array $translationStatuses = [],
    int $parentId = null,
    string $taxonomyId = null,
    int $termId = null,
    int $limit = 10,
    int $offset = 0,
    array $sorting = null
  ) {
    $this->type                = $type;
    $this->title               = $title;
    $this->publicationStatus   = $publicationStatus;
    $this->languages           = $languages;
    $this->translationStatuses = $translationStatuses;
    $this->parentId            = $parentId;
    $this->taxonomyId          = $taxonomyId;
    $this->termId              = $termId;
    $this->limit               = $limit;
    $this->offset              = $offset;
    $this->sortingCriteria     = $sorting ?
      new SortingCriteria( $sorting['by'], $sorting['order'] ) :
      null;
  }


  public function getType(): string {
    return $this->type;
  }


  /** @return ?string */
  public function getTitle() {
    return $this->title;
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


  /** @return ?int */
  public function getParentId() {
    return $this->parentId;
  }


  /** @return ?string */
  public function getTaxonomyId() {
    return $this->taxonomyId;
  }


  /** @return ?int */
  public function getTermId() {
    return $this->termId;
  }


  public function getLimit(): int {
    return $this->limit;
  }


  /**
   * @return SortingCriteria|null
   */
  public function getSortingCriteria() {
    return $this->sortingCriteria;
  }


  /**
   * @param int $limit
   *
   * @return void
   */
  public function setLimit( int $limit ) {
    $this->limit = $limit;
  }


  public function getOffset(): int {
    return $this->offset;
  }


  /**
   * @param int $offset
   *
   * @return void
   */
  public function setOffset( int $offset ) {
    $this->offset = $offset;
  }


  /**
   * @param SortingCriteria $sortingCriteria
   *
   * @return void
   */
  public function setSortingCriteria( SortingCriteria $sortingCriteria ) {
    $this->sortingCriteria = $sortingCriteria;
  }


}
