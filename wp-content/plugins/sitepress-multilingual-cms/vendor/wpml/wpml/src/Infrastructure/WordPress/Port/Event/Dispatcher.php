<?php

namespace WPML\Infrastructure\WordPress\Port\Event;

use WPML\Core\Port\Event\DispatcherInterface;
use WPML\Core\Port\Event\Event;

class Dispatcher implements DispatcherInterface {


  /**
  * @param Event $event
  *
  * @return void
  */
  public function dispatch( Event $event ) {
    do_action( $event->getName(), ...$event->getPayload() );
  }


}
