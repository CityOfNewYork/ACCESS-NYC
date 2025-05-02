<?php

namespace WPML\Core\Component\ATE\Application\Service;

use WPML\Core\Component\ATE\Application\Service\Dto\EngineDto;
use WPML\Core\Component\ATE\Application\Service\Dto\UpdateEngineDto;

interface EnginesServiceInterface {


  /**
   * @return EngineDto[]
   * @throws EngineServiceException
   */
  public function getList(): array;


  /**
   * @param UpdateEngineDto[] $engines
   *
   * @return void
   * @throws EngineServiceException
   *
   */
  public function update( array $engines );


}
