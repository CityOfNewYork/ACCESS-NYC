<?php

namespace WPML\Core\Component\Post\Application\Query;

interface PermalinkQueryInterface {


  /**
   * @param int $postId
   *
   * @return string | bool
   */
  public function getPermalink( int $postId );


}
