<?php

namespace WPML\Core\Component\Post\Application\Query\Criteria;

use WPML\PHP\ConstructableFromArrayInterface;
use WPML\PHP\ConstructableFromArrayTrait;

/**
 * @implements ConstructableFromArrayInterface<HierarchicalPostCriteria>
 */
final class HierarchicalPostCriteria  implements ConstructableFromArrayInterface {

  /** @use ConstructableFromArrayTrait<HierarchicalPostCriteria> */
  use ConstructableFromArrayTrait;

  /**
   * @var string
   */
  private $type;

  /**
   * @var string
   */
  private $sourceLanguageCode;

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
   * @param string $type
   * @param string $sourceLanguageCode
   * @param string|null $search
   * @param int|null $limit
   * @param int|null $offset
   */
  public function __construct(
    string $type,
    string $sourceLanguageCode,
    string $search = null,
    int $limit = null,
    int $offset = null
  ) {
    $this->type = $type;
    $this->sourceLanguageCode = $sourceLanguageCode;
    $this->search = $search;
    $this->limit = $limit;
    $this->offset = $offset;
  }


  /** @return ?string */
  public function getSearch() {
    return $this->search;
  }


  /** @return ?int */
  public function getLimit() {
    return $this->limit;
  }


  /** @return ?int */
  public function getOffset() {
    return $this->offset;
  }


  public function getType(): string {
    return $this->type;
  }


  public function getSourceLanguageCode(): string {
    return $this->sourceLanguageCode;
  }


}
