<?php

namespace WPML\Core\Component\Translation\Application\Query;

/**
 * It represents translations of WP Posts ( posts, pages, custom post types and etc ), but not packages or strings.
 */
interface PostTranslationQueryInterface {


  /**
   * If a given post is original, it returns the post ID.
   *
   * @param int $translatedPostId
   *
   * @return int
   */
  public function getOriginalPostId( int $translatedPostId ): int;


}
