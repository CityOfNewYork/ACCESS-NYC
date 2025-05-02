<?php

namespace WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config;

use WPML\UserInterface\Web\Core\SharedKernel\Config\Page;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Script;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Style;

interface PageInterface {


  /**
   * @param callable $onLoadPageHandle
   * @return void
   */
  public function register( Page $page, $onLoadPageHandle );


  /**
   * @return void
   */
  public function loadStyle( Style $style );


  /**
   * @return void
   */
  public function registerScript( Script $script );


  /**
   * @return void
   */
  public function loadScript( Script $script );


  /**
   * @param array<mixed> $data
   * @return void
   */
  public function provideDataForScript(
    Script $script,
    string $jsWindowKey,
    $data
  );


}
