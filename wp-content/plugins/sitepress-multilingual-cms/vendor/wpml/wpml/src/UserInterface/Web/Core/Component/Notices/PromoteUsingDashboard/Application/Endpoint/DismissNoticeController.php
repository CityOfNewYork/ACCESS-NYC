<?php

namespace WPML\UserInterface\Web\Core\Component\Notices\PromoteUsingDashboard\Application\Endpoint;

use WPML\Core\Port\Endpoint\EndpointInterface;

class DismissNoticeController implements EndpointInterface {


  public function handle( $requestData = null ): array {
    // get the current translator id
    // get data from wp_options that tells current user manual translation tries and if notice is dismissed
    // set the notice dismissed for the current translator
    return [];
  }


}
