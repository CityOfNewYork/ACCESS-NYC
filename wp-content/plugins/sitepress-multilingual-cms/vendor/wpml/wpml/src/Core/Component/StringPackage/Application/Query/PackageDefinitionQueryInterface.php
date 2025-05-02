<?php

namespace WPML\Core\Component\StringPackage\Application\Query;

use WPML\Core\Component\StringPackage\Application\Query\Dto\PackageDefinitionDto;

interface PackageDefinitionQueryInterface {


  /**
   * @return array<string, PackageDefinitionDto>
   */
  public function getInfoList(): array;


  /**
   * @return string[]
   */
  public function getNamesList(): array;


}
