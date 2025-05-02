<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application;

interface DashboardTabsInterface {


  public function wrapTabsAroundContent( string $content ): string;


}
