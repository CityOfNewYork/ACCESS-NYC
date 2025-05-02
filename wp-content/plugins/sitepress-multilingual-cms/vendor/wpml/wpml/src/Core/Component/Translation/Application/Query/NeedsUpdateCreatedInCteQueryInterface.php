<?php

namespace WPML\Core\Component\Translation\Application\Query;

interface NeedsUpdateCreatedInCteQueryInterface {


  /**
   * It gets number of items ( post or packages ) which have at least one translation with status: "needs update"
   * which originally was created in CTE.
   *
   * @return int
   */
  public function get(): int;


}
