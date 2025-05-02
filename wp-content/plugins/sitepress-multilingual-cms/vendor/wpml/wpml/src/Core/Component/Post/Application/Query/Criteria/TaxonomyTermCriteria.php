<?php

namespace WPML\Core\Component\Post\Application\Query\Criteria;

use WPML\PHP\ConstructableFromArrayInterface;
use WPML\PHP\ConstructableFromArrayTrait;

/**
 * @implements ConstructableFromArrayInterface<TaxonomyTermCriteria>
 */
final class TaxonomyTermCriteria implements ConstructableFromArrayInterface
{

  /** @use ConstructableFromArrayTrait<TaxonomyTermCriteria> */
    use ConstructableFromArrayTrait;

  /**
   * @var string
   */
    private $taxonomyId;

  /**
   * @var string|null
   */
    private $search;

  /**
   * @var int|null
   */
    private $limit;

  /**
   * @var int|null
   */
    private $offset;

  /**
   * @var string
   */
    private $sourceLanguageCode;


  /**
   * @param string      $taxonomyId
   * @param string|null $search
   * @param int|null    $limit
   * @param int|null    $offset
   */
  public function __construct(
        string $taxonomyId,
        string $sourceLanguageCode,
        string $search = null,
        int $limit = null,
        int $offset = null
    ) {
      $this->taxonomyId         = $taxonomyId;
      $this->search             = $search;
      $this->limit              = $limit;
      $this->offset             = $offset;
      $this->sourceLanguageCode = $sourceLanguageCode;
  }


  public function getTaxonomyId(): string {
      return $this->taxonomyId;
  }


  public function getSourceLanguageCode(): string {
      return $this->sourceLanguageCode;
  }


  /**
   * @return string | null
   */
  public function getSearch() {
      return $this->search;
  }


  /**
   * @return int | null
   */
  public function getLimit() {
      return $this->limit;
  }


  /**
   * @return int | null
   */
  public function getOffset() {
      return $this->offset;
  }


}
