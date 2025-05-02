<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPopulatedItemSections;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchPopulatedTypesCriteriaBuilder;
use WPML\Core\Component\Post\Application\Query\SearchPopulatedTypesQueryInterface;
use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\PHP\Exception\Exception;
use WPML\PHP\Exception\InvalidArgumentException;

/**
 * @phpstan-type RequestData=array{
 *   itemSectionIds: string[],
 *   sourceLanguageCode: string,
 *   targetLanguageCode?: string,
 *   translationStatuses?: int[],
 *   publicationStatus?: string,
 *  }
 */
class GetPopulatedItemSectionsController implements EndpointInterface {

  /**
   * @var SearchPopulatedTypesQueryInterface
   */
  private $searchPopulatedTypesQuery;

  /**
   * @var PopulatedItemSectionsFilterInterface
   */
  private $populatedItemSectionsFilter;

  /**
   * @var SearchPopulatedTypesCriteriaBuilder
   */
  private $criteriaBuilder;


  public function __construct(
    SearchPopulatedTypesQueryInterface $searchPopulatedTypesQuery,
    PopulatedItemSectionsFilterInterface $populatedItemSectionsFilter,
    SearchPopulatedTypesCriteriaBuilder $criteriaBuilder
  ) {
    $this->searchPopulatedTypesQuery   = $searchPopulatedTypesQuery;
    $this->populatedItemSectionsFilter = $populatedItemSectionsFilter;
    $this->criteriaBuilder             = $criteriaBuilder;
  }


  /**
   * @param array<string,mixed> $requestData
   *
   * @return array<string,mixed>
   * @throws InvalidArgumentException|Exception
   */
  public function handle( $requestData = null ): array {
    $requestData = $this->validateRequestData( $requestData ?? [] );

    try {
      $criteria = $this->criteriaBuilder->build( $requestData );
    } catch ( InvalidArgumentException $e ) {
      throw new InvalidArgumentException(
        'The request data for GetPopulatedItemSections is not valid.' . $e->getMessage()
      );
    }

    $itemSectionIds = $requestData['itemSectionIds'];

    $populatedPostItems = $this->searchPopulatedTypesQuery->get( $criteria );

    foreach ( $itemSectionIds as $key => $itemSectionId ) {
      if (
        strpos( $itemSectionId, 'post/' ) === 0 &&
        ! in_array( str_replace( 'post/', '', $itemSectionId ), $populatedPostItems )
      ) {
        // If it's a post type, and not populated, remove it.
        unset( $itemSectionIds[ $key ] );
      }
    }

    $itemSectionIds = array_values( $itemSectionIds );

    $itemSectionIds = $this->populatedItemSectionsFilter->filter( $itemSectionIds, $criteria );

    return [
      'itemSectionIds' => $itemSectionIds,
    ];
  }


  /**
   * @param array<string,mixed> $requestData
   *
   * @return array{
   *   itemSectionIds: string[],
   *   sourceLanguageCode?: string,
   *   targetLanguageCode?: string,
   *   translationStatuses?: non-empty-list<int>,
   *   publicationStatus?: string
   * }
   * @throws InvalidArgumentException
   */
  private function validateRequestData( array $requestData ): array {
    if (
      ! isset( $requestData['itemSectionIds'] ) ||
      ! is_array( $requestData['itemSectionIds'] )
    ) {
      throw new InvalidArgumentException( 'itemSectionIds is required' );
    }

    foreach ( $requestData['itemSectionIds'] as $itemSectionId ) {
      if ( ! is_string( $itemSectionId ) ) {
        throw new InvalidArgumentException( 'All itemSectionIds must be numeric' );
      }
    }

    $validated = [
      'itemSectionIds' => array_map( 'strval', $requestData['itemSectionIds'] )
    ];

    if ( isset( $requestData['sourceLanguageCode'] ) && is_string( $requestData['sourceLanguageCode'] ) ) {
      $validated['sourceLanguageCode'] = $requestData['sourceLanguageCode'];
    }

    if ( isset( $requestData['targetLanguageCode'] ) && is_string( $requestData['targetLanguageCode'] ) ) {
      $validated['targetLanguageCode'] = $requestData['targetLanguageCode'];
    }

    if ( isset( $requestData['translationStatuses'] ) && is_array( $requestData['translationStatuses'] ) ) {
      foreach ( $requestData['translationStatuses'] as $translationStatus ) {
        if ( is_numeric( $translationStatus ) ) {
          $validated['translationStatuses'][] = (int) $translationStatus;
        }
      }
    }

    if ( isset( $requestData['publicationStatus'] ) && is_string( $requestData['publicationStatus'] ) ) {
      $validated['publicationStatus'] = $requestData['publicationStatus'];
    }

    return $validated;
  }


}
