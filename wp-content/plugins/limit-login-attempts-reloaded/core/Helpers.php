<?php

/**
 * Class LLA_Helpers
 */
class LLA_Helpers {

    /**
     * @param string $msg
     */
    public static function show_error( $msg = '' ) {
        if( empty( $msg ) ) {
            return;
        }

        echo '<div id="message" class="updated fade"><p>' . $msg . '</p></div>';
    }
}