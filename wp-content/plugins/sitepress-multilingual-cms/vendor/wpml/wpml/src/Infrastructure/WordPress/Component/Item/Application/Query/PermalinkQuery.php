<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query;

use WPML\Core\Component\Post\Application\Query\PermalinkQueryInterface;

class PermalinkQuery implements PermalinkQueryInterface {


  public function getPermalink( int $postId ) {
    return get_permalink( $postId, false );
  }


}
