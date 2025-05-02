<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query;

use WPML\Core\Component\Post\Application\Query\Dto\PublicationStatusDto;
use WPML\Core\Component\Post\Application\Query\PublicationStatusQueryInterface;

class PublicationStatusQuery implements PublicationStatusQueryInterface {
  const ALLOWED_DEFAULT_STATUSES = [
    'publish',
    'draft',
    'pending',
    'future',
    'private',
  ];


  public function getNotInternalStatuses(): array {
    $wp_post_statuses = $GLOBALS['wp_post_statuses'];

    $publicationStatuses = [];
    foreach ( $wp_post_statuses as $key => $status ) {
      if ( ! $status->internal && in_array( $key, self::ALLOWED_DEFAULT_STATUSES ) ) {
          $publicationStatuses[] = new PublicationStatusDto( $key, $status->label );
      }
    }

    return $publicationStatuses;
  }


}
