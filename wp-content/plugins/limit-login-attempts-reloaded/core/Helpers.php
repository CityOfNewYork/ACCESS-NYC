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

    /**
     * @param $log
     *
     * @return array
     */
    public static function sorted_log_by_date( $log ) {
        $new_log = array();

        if ( ! is_array( $log ) || empty( $log ) ) {
            return $new_log;
        }

        foreach ( $log as $ip => $users ) {

            if ( ! empty( $users ) ) {
                foreach ( $users as $user_name => $info ) {

                    if ( is_array( $info ) ) { // For new plugin version
                        $new_log[ $info['date'] ] = array(
                            'ip'       => $ip,
                            'username' => $user_name,
                            'counter'  => $info['counter'],
                            'gateway'  => ( isset( $info['gateway'] ) ) ? $info['gateway'] : '-',
                            'unlocked' => !empty( $info['unlocked'] ),
                        );
                    } else { // For old plugin version
                        $new_log[0] = array(
                            'ip'       => $ip,
                            'username' => $user_name,
                            'counter'  => $info,
                            'gateway'  => '-',
                            'unlocked' => false,
                        );
                    }

                }
            }

        }

        krsort( $new_log );

        return $new_log;
    }
}