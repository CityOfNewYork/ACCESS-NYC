<?php

namespace WPML\PHP\Logger;


class DebugFileLogger implements LoggerInterface {

  /**
   * @var LoggerInterface | null
   */
  private static $instance;


  /**
   * @param LoggerInterface $logger
   *
   * @return void
   */
  public static function load( LoggerInterface $logger ) {
    self::$instance = $logger;
  }


  public static function getInstance(): LoggerInterface {
    if ( ! self::$instance ) {
      self::$instance = new DebugFileLogger();
    }

    return self::$instance;
  }


  /**
   * @param string $level
   * @param string $message
   *
   * @return void
   */


  private function log( $level, $message ) {
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
      return;
    }

    error_log( ' [' . $level . '] ' . $message );
  }


  /**
   * @param string $message
   *
   * @return void
   */
  public function error( $message ) {
    $this->log( 'error', $message );
  }


  /**
   * @param string $message
   *
   * @return void
   */
  public function notice( $message ) {
    $this->log( 'notice', $message );
  }


}
