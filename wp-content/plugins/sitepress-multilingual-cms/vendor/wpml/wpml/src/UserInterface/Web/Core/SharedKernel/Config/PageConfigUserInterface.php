<?php

namespace WPML\UserInterface\Web\Core\SharedKernel\Config;

/**
* Interface PageConfigUserInterface
*
* If the 'controller' of a page implements this interface, the controller will
* get the Page object to make use of the page object.
*/
interface PageConfigUserInterface {


  /** @return void */
  public function setPageConfig( Page $page );


}
