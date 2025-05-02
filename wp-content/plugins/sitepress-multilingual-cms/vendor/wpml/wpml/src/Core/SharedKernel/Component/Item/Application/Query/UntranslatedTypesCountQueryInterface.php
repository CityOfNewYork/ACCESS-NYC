<?php

namespace WPML\Core\SharedKernel\Component\Item\Application\Query;

use WPML\Core\SharedKernel\Component\Item\Application\Query\Dto\UntranslatedTypeCountDto;

interface UntranslatedTypesCountQueryInterface {


  /** @return UntranslatedTypeCountDto[] */
  public function get(): array;


}
