<?php

namespace WPML\Core\SharedKernel\Component\Post\Application\Hook;

interface PostTypeFilterInterface {


  /**
   * @param array<string, mixed> $postTypes
   *
   * @return array<string, mixed>
   */
  public function filter( array $postTypes );


}
