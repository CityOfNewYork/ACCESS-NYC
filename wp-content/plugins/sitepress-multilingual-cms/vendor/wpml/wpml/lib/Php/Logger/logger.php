<?php

namespace WPML\PHP\Logger;


/**
 * @param string $message
 *
 * @return void
 */
function error( string $message ) {
  DebugFileLogger::getInstance()->error( $message );
}


/**
 * @param string $message
 *
 * @return void
 */
function notice( string $message ) {
  DebugFileLogger::getInstance()->notice( $message );
}
