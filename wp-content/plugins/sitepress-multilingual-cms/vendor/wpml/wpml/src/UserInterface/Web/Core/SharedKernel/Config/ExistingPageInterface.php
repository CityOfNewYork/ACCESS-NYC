<?php

namespace WPML\UserInterface\Web\Core\SharedKernel\Config;

interface ExistingPageInterface {


  /** @return bool */
  public function isActive();


  /** @return void */
  public function renderNotice( Notice $notice );


}
