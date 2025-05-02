<?php

namespace WPML\Core\Component\Post\Application\Query;

use WPML\Core\Component\Post\Application\Query\Dto\PublicationStatusDto;

interface PublicationStatusQueryInterface {


  /**
   * @return array<PublicationStatusDto>
   */
  public function getNotInternalStatuses(): array;


}
