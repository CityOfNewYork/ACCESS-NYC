<?php

namespace WPML\Core\SharedKernel\Component\User\Application\Query;

use WPML\Core\SharedKernel\Component\User\Application\Query\Dto\UserDto;

interface UserQueryInterface {


  /**
   * @return UserDto|null
   */
  public function getCurrent();


}
