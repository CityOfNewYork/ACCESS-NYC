<?php

/**
 * Class LLA_Logger
 */
class LLA_Logger {

    private static $_log_file_name = 'log.txt';

    /**
     * TODO
     * @param $msg
     * @return int|void
     */
    public static function add_log( $msg ) {
        if( ! $msg ) {
            return;
        }

        return file_put_contents( LLA_PLUGIN_DIR . DIRECTORY_SEPARATOR . self::$_log_file_name, $msg . "\n\r", FILE_APPEND );
    }

}