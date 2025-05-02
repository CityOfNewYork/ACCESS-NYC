<?php

namespace WPML\UserInterface\Web\Legacy\Component\Dashboard;

use WPML\UserInterface\Web\Core\Component\Dashboard\Application\DashboardTabsInterface;

class DashboardTabs implements DashboardTabsInterface {


  public function wrapTabsAroundContent( string $content ): string {
    ob_start();
    \WPML_TM_Menus_Management::getInstance()->renderEmbeddedDashboard(
      function() use ( $content ) {
        echo $content;
      }
    );

    $content = ob_get_clean();
    return $content ? $content : '';
  }


}
