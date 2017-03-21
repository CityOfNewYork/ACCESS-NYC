<?php

class FacetWP_Updater
{
    public $slug;
    public $version;


    function __construct() {
        $this->slug = 'facetwp';
        $this->version = FACETWP_VERSION;
        $this->license = get_option( 'facetwp_license' );

        add_action( 'init', array( $this, 'init' ) );
    }


    /*
     * Initialize actions and filters
     */
    function init() {
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

        $request = wp_remote_post( 'http://api.facetwp.com', array(
            'body' => array(
                'action'    => 'version',
                'slug'      => $this->slug,
                'license'   => $this->license,
                'host'      => FWP()->helper->get_http_host(),
            )
        ) );

        if ( ! is_wp_error( $request ) || 200 == wp_remote_retrieve_response_code( $request ) ) {
            $response = unserialize( $request['body'] );

            if ( ! empty( $response ) ) {
                if ( version_compare( $this->version, $response->version, '<' ) ) {
                    $transient->response['facetwp/index.php'] = (object) array(
                        'slug'          => $this->slug,
                        'plugin'        => FACETWP_BASENAME,
                        'new_version'   => $response->version,
                        'url'           => $response->url,
                        'package'       => $response->package,
                    );
                }

                update_option( 'facetwp_activation', json_encode( $response->activation ) );
            }
        }

        return $transient;
    }


    /**
     * Get plugin info for the "View Details" popup
     */
    function plugins_api( $default = false, $action, $args ) {
        if ( 'plugin_information' == $action && $this->slug == $args->slug ) {
            $request = wp_remote_post( 'http://api.facetwp.com', array(
                'body' => array( 'action' => 'info', 'slug' => $this->slug )
            ) );

            if ( ! is_wp_error( $request ) || 200 == wp_remote_retrieve_response_code( $request ) ) {
                $response = unserialize( $request['body'] );

                // Trigger update notification
                if ( version_compare( $this->version, $response->version, '<' ) ) {
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
