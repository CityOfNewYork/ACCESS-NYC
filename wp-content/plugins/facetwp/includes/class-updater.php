<?php

class FacetWP_Updater
{

    function __construct() {
        add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
        add_action( 'in_plugin_update_message-' . FACETWP_BASENAME, array( $this, 'in_plugin_update_message' ), 10, 2 );
    }


    /**
     * Connect to the activation server to get update details
     */
    function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        // Use cache?
        $now = strtotime( 'now' );
        $response = get_option( 'facetwp_updater_response', '' );
        $ts = (int) get_option( 'facetwp_updater_last_checked' );

        if ( $ts + 1800 < $now || empty( $response ) ) {
            $request = wp_remote_post( 'http://api.facetwp.com', array(
                'body' => array(
                    'action'    => 'version',
                    'slug'      => 'facetwp',
                    'license'   => FWP()->helper->get_license_key(),
                    'host'      => FWP()->helper->get_http_host(),
                    'php_v'     => phpversion(),
                )
            ) );

            if ( ! is_wp_error( $request ) || 200 == wp_remote_retrieve_response_code( $request ) ) {
                $response = unserialize( $request['body'] );
            }

            update_option( 'facetwp_updater_response', $response );
            update_option( 'facetwp_updater_last_checked', $now );
        }

        if ( ! empty( $response ) ) {
            if ( version_compare( FACETWP_VERSION, $response->version, '<' ) ) {
                $transient->response['facetwp/index.php'] = (object) array(
                    'slug'          => 'facetwp',
                    'plugin'        => FACETWP_BASENAME,
                    'new_version'   => $response->version,
                    'url'           => $response->url,
                    'package'       => $response->package,
                );
            }

            update_option( 'facetwp_activation', json_encode( $response->activation ) );
        }

        return $transient;
    }


    /**
     * Get plugin info for the "View Details" popup
     */
    function plugins_api( $default = false, $action, $args ) {
        if ( 'plugin_information' == $action && 'facetwp' == $args->slug ) {
            $request = wp_remote_post( 'http://api.facetwp.com', array(
                'body' => array( 'action' => 'info', 'slug' => 'facetwp' )
            ) );

            if ( ! is_wp_error( $request ) || 200 == wp_remote_retrieve_response_code( $request ) ) {
                $response = unserialize( $request['body'] );

                // Trigger update notification
                if ( version_compare( FACETWP_VERSION, $response->version, '<' ) ) {

                    // Populate the "download_link" property
                    $transient = get_site_transient( 'update_plugins' );
                    if ( is_object( $transient ) && isset( $transient->response['facetwp/index.php'] ) ) {
                        if ( ! empty( $transient->response['facetwp/index.php']->package ) ) {
                            $response->download_link = $transient->response['facetwp/index.php']->package;
                        }
                    }

                    return $response;
                }
            }
        }

        return $default;
    }


    /**
     * Display an update message for plugin list screens
     */
    function in_plugin_update_message( $plugin_data, $r ) {
        $activation = get_option( 'facetwp_activation' );

        if ( ! empty( $activation ) ) {
            $activation = json_decode( $activation, true );

            if ( empty( $activation['status'] ) || 'success' != $activation['status'] ) {
                echo '<br />' . __( 'Please activate or renew your license for automatic updates.', 'fwp' );
            }
        }
    }
}

new FacetWP_Updater();
