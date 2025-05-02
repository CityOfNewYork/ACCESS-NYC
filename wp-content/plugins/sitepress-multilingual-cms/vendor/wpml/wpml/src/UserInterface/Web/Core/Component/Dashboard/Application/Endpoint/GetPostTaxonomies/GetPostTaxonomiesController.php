<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPostTaxonomies;

use WPML\Core\Component\Post\Application\Query\Criteria\TaxonomyCriteria;
use WPML\Core\Component\Post\Application\Query\Dto\PostTaxonomyDto;
use WPML\Core\Component\Post\Application\Query\TaxonomyQueryInterface;
use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\PHP\Exception\Exception;
use WPML\PHP\Exception\InvalidArgumentException;


class GetPostTaxonomiesController implements EndpointInterface {

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
   * @throws Exception Some system related error.
   * @throws InvalidArgumentException The requestData was not valid.
   *
   * @return array<int, mixed>
   */
  public function handle( $requestData = null ): array {
    $requestData = $requestData ?: [];

    try {
      $items = $this->taxonomyQuery->getTaxonomies( TaxonomyCriteria::fromArray( $requestData ) );
    } catch ( InvalidArgumentException $e ) {
      throw new InvalidArgumentException(
        'The request data for GetTaxonomies is not valid.'
      );
    }

    return array_map(
      function ( PostTaxonomyDto $taxonomy ) {
        return [
          'id' => $taxonomy->getId(),
          'name' => $taxonomy->getName(),
          'postTypeIds' => $taxonomy->getPostTypeIds(),
        ];
      },
      $items
    );
  }


}
