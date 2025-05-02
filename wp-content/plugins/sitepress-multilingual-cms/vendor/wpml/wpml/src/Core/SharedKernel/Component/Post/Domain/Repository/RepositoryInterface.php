<?php

namespace WPML\Core\SharedKernel\Component\Post\Domain\Repository;

use WPML\Core\SharedKernel\Component\Post\Domain\Post;
use WPML\PHP\Exception\InvalidItemIdException;

interface RepositoryInterface {


  /**
   * @param int $postId
   *
   * @return Post
   * @throws InvalidItemIdException
   */
  public function getById( int $postId ): Post;


}
