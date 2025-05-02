<?php

namespace WPML\Core\Component\Post\Application\Query;

use WPML\Core\Component\Post\Application\Query\Criteria\TaxonomyCriteria;
use WPML\Core\Component\Post\Application\Query\Criteria\TaxonomyTermCriteria;
use WPML\Core\Component\Post\Application\Query\Dto\PostTaxonomyDto;
use WPML\Core\Component\Post\Application\Query\Dto\PostTermDto;

interface TaxonomyQueryInterface {


  /**
   * @return array<PostTaxonomyDto>
   */
  public function getTaxonomies( TaxonomyCriteria $criteria ): array;


  /**
   * @param TaxonomyTermCriteria $criteria
   *
   * @return array<PostTermDto>
   */
  public function getTerms( TaxonomyTermCriteria $criteria );


}
