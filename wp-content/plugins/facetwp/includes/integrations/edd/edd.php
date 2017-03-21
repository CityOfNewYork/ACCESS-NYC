<?php

class FacetWP_Integration_EDD
{

    function __construct() {
        add_filter( 'edd_downloads_query', array( $this, 'edd_downloads_query' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'front_scripts' ) );
    }


    /**
     * Trigger some EDD code on facetwp-loaded
     * @since 2.0.4
     */
    function front_scripts() {
        wp_enqueue_script( 'facetwp-edd', FACETWP_URL . '/includes/integrations/edd/edd.js', array( 'jquery' ), FACETWP_VERSION );
    }


    /**
     * Intercept EDD's [downloads] shortcode
     * @since 2.0.4
     */
    function edd_downloads_query( $query ) {
        if ( ! empty( FWP()->facet->query_args ) ) {
            $query = array_merge( $query, FWP()->facet->query_args );
        }

        return $query;
    }
}


if ( is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) ) {
    new FacetWP_Integration_EDD();
}
