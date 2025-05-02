<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Hook;

use WPML\Core\SharedKernel\Component\Post\Application\Hook\PostTypeFilterInterface;

interface DashboardTranslatablePostTypesFilterInterface extends PostTypeFilterInterface {


  /**
   * @param array<string, mixed> $postTypes
   *
   * @return array<string, mixed>
   */
  public function filter( array $postTypes );


}
