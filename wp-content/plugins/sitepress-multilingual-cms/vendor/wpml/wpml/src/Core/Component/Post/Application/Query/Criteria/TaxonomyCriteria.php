<?php

namespace WPML\Core\Component\Post\Application\Query\Criteria;

use WPML\PHP\ConstructableFromArrayInterface;
use WPML\PHP\ConstructableFromArrayTrait;

/**
 * @implements ConstructableFromArrayInterface<TaxonomyCriteria>
 */
final class TaxonomyCriteria implements ConstructableFromArrayInterface {

  /** @use ConstructableFromArrayTrait<TaxonomyCriteria> */
  use ConstructableFromArrayTrait;

  /**
   * @var string
   */
  private $sourceLanguageCode;


  public function __construct(
    string $sourceLanguageCode
  ) {
    $this->sourceLanguageCode = $sourceLanguageCode;
  }


  public function getSourceLanguageCode(): string {
    return $this->sourceLanguageCode;
  }


}
