<?php

namespace WPML\UserInterface\Web\Core\Port\Asset;

use WPML\UserInterface\Web\Core\SharedKernel\Config\Script;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Style;

interface AssetInterface {


  /**
   * @param Script $script
   * @return void
   */
  public function enqueueScript( Script $script );


  /**
   * @param Style $style
   * @return void
   */
  public function enqueueStyle( Style $style );


}
