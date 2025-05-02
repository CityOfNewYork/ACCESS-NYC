<?php

namespace WPML\PHP\Logger;

interface LoggerInterface {

  /**
   * @param string $message
   *
   * @return void
   */


  public function error( $message );


  /**
   * @param string $message
   *
   * @return void
   */
  public function notice( $message );


}
