<?php

namespace WPML\UserInterface\Web\Core\SharedKernel\Config;

/**
* Interface PageRenderInterface
*
* If the 'controller' of a page implements this interface, the controller will
* be responsible for rendering the page.
*
* Otherwise the default rendering logic will be used (title + div with id).
*/
interface PageRenderInterface {


  /** @return void */
  public function render();


}
