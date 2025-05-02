<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPostTerms;

use WPML\Core\Component\Post\Application\Query\Criteria\TaxonomyTermCriteria;
use WPML\Core\Component\Post\Application\Query\Dto\PostTermDto;
use WPML\Core\Component\Post\Application\Query\TaxonomyQueryInterface;
use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\PHP\Exception\Exception;
use WPML\PHP\Exception\InvalidArgumentException;


class GetPostTermsController implements EndpointInterface {

  /** @var TaxonomyQueryInterface */
  private $taxonomyQuery;


  public function __construct(
    TaxonomyQueryInterface $taxonomyQueryInterface
  ) {
    $this->taxonomyQuery = $taxonomyQueryInterface;
  }


  /**
   * @param array<string,mixed> $requestData
   *
   * @return array<array<string, mixed>> It should return array with sequential keys.
   * @throws InvalidArgumentException The requestData was not valid.
   *
   * @throws Exception Some system related error.
   */
  public function handle( $requestData = null ): array {
    $requestData = $requestData ?: [];

    try {
      /** @var TaxonomyTermCriteria $criteria */
      $criteria = TaxonomyTermCriteria::fromArray( $requestData );
      $items    = $this->taxonomyQuery->getTerms( $criteria );
    } catch ( InvalidArgumentException $e ) {
      throw new InvalidArgumentException(
        'The request data for GetTerms is not valid.'
      );
    }

    return array_values(
      array_map(
        function ( PostTermDto $term ) {
          return [
            'id'   => $term->getId(),
            'name' => $term->getName()
          ];
        },
        $items
      )
    );
  }


}
