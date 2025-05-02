<?php

namespace WPML\UserInterface\Web\Core\SharedKernel\Config;

/**
* Interface NoticeRenderInterface
*
* If the 'controller' of a notice implements this interface, the controller will
* be responsible for rendering the page.
*
* Otherwise the default rendering logic will be used (div with id).
*/
interface NoticeRenderInterface {


  /** @return void */
  public function render();


}
