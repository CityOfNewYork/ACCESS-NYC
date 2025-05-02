<?php

namespace WPML\UserInterface\Web\Core\SharedKernel\Config;

/**
* Interface NoticeRequirementsInterface
*
* If the 'controller' of a notice implements this interface, the controller
* determines if the notice should be displayed.
*/
interface NoticeRequirementsInterface {


  /** @return bool */
  public function requirementsMet();


}
