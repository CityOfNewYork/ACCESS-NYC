<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder;

use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\ManyLanguagesStrategy\QueryBuilderFactory as ManyLanguagesFactory;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\MultiJoinStrategy\QueryBuilderFactory as MultiJoinFactory;

class QueryBuilderResolver {

  const TARGET_LANGUAGES_THRESHOLD = 29;

  /** @var LanguagesQueryInterface */
  private $languageQuery;

  /** @var ManyLanguagesFactory */
  private $manyLanguagesFactory;

  /** @var MultiJoinFactory */
  private $multiJoinFactory;


  public function __construct(
    LanguagesQueryInterface $languagesQuery,
    ManyLanguagesFactory $manyLanguagesFactory,
    MultiJoinFactory $multiJoinFactory
  ) {
    $this->languageQuery        = $languagesQuery;
    $this->manyLanguagesFactory = $manyLanguagesFactory;
    $this->multiJoinFactory     = $multiJoinFactory;
  }


  public function resolveSearchQueryBuilder(): SearchQueryBuilderInterface {
    if ( $this->isLimitReached() ) {
      return $this->manyLanguagesFactory->createSearchQueryBuilder();
    }

    return $this->multiJoinFactory->createSearchQueryBuilder();
  }


  public function resolveSearchPopulatedTypesQueryBuilder(): SearchPopulatedTypesQueryBuilderInterface {
    if ( $this->isLimitReached() ) {
      return $this->manyLanguagesFactory->createSearchPopulatedTypesQueryBuilder();
    }

    return $this->multiJoinFactory->createSearchPopulatedTypesQueryBuilder();
  }


  private function isLimitReached(): bool {
    return count( $this->languageQuery->getSecondary() ) >= self::TARGET_LANGUAGES_THRESHOLD;
  }


}
