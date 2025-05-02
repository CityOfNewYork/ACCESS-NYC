<?php

namespace WPML\Core\Component\ATE\Application\Query;

interface GlossaryInterface {


  /**
   * @return int
   * @throws GlossaryException
   */
  public function getGlossaryCount(): int;


}
