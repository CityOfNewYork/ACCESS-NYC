<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts;

/**
 * @phpstan-import-type Post from GetPostsController
 * @phpstan-import-type SearchCriteriaRaw from GetPostsController
 */
interface PostsFilterInterface {


  /**
   * @param Post[]         $posts
   * @param SearchCriteriaRaw $searchCriteria
   *
   * @return Post[]
   */
  public function filter( array $posts, array $searchCriteria ): array;


  public function filterViewLink( string $viewLink, int $postId, string $postType, string $languageCode ): string;


  public function filterEditLink( string $editLink, int $postId, string $postType, string $languageCode ): string;


}
