<?php

namespace WPML\Core\Port\Event;

interface DispatcherInterface {


  /**
   * @param Event $event
   * @return void
   */
  public function dispatch( Event $event );


}
