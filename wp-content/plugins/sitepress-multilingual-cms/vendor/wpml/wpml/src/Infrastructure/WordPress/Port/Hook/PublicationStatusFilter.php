<?php
namespace WPML\Infrastructure\WordPress\Port\Hook;

use WPML\Core\Component\Post\Application\Query\Dto\PublicationStatusDto;

abstract class PublicationStatusFilter {

  const NAME = 'wpml_publication_status_dto_filter';


  /**
   * @param PublicationStatusDto[] $publicationStatusDtos
   * @return PublicationStatusDto[]
   */
  public function filterByDto( array $publicationStatusDtos ) {
    // Make it plain, key value pairs
    $postStatuses = array_reduce(
      $publicationStatusDtos,
      function ( array $carry, PublicationStatusDto $publicationStatus ) {
        $carry[ $publicationStatus->getId() ] = $publicationStatus->getLabel();
        return $carry;
      },
      []
    );

    /** @var array<string, string> $postStatuses */
    $postStatuses = apply_filters( static::NAME, $postStatuses );

    $publicationStatusDtos = [];
    foreach ( $postStatuses as $key => $value ) {
      $publicationStatusDtos[] = new PublicationStatusDto( $key, $value );
    }
    return $publicationStatusDtos;
  }


}
