<?php

namespace WPML\UserInterface\Web\Core\SharedKernel\Config;

/**
* Interface PageRequirementsInterface
*
* If the 'controller' of a page implements this interface, the controller
* determines if the notice should be displayed.
*/
interface PageRequirementsInterface {


  /** @return bool */
  public function requirementsMet();


}
