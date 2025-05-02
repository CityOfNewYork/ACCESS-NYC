<?php

namespace WPML\Core\SharedKernel\Component\Post\Domain\Repository;

interface MetadataRepositoryInterface {


  /**
   * @return mixed Returns value of the specified meta key for the given post ID.
   */
  public function get( int $postId, string $metaKey );


  /**
   * @return bool|int
   */
  public function update( int $postId, string $metaKey, string $value );


}
