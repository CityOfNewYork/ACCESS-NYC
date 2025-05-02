<?php

namespace WPML\Core\SharedKernel\Component\Post\Application\Query;

use WPML\Core\SharedKernel\Component\Post\Application\Query\Dto\PostTypeDto;

interface TranslatableTypesQueryInterface {


  /**
   * @return array<PostTypeDto>
   */
  public function getTranslatable(): array;


  /**
   * @return array<PostTypeDto>
   */
  public function getDisplayAsTranslated(): array;


  /**
   * @return array<PostTypeDto>
   */
  public function getTranslatableWithoutDisplayAsTranslated(): array;


}
