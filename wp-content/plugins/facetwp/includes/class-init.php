<?php

class FacetWP_Init
{

    function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }


    /**
     * Initialize classes and WP hooks
     */
    function init() {

        // i18n
        $this->load_textdomain();

        // is_plugin_active
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        // classes
        foreach ( array( 'helper', 'ajax', 'facet', 'indexer', 'display', 'upgrade' ) as $f ) {
            include( FACETWP_DIR . "/includes/class-{$f}.php" );
        }

        new FacetWP_Upgrade();
        FWP()->helper       = new FacetWP_Helper();
        FWP()->facet        = new FacetWP_Facet();
        FWP()->indexer      = new FacetWP_Indexer();
        FWP()->display      = new FacetWP_Display();
        FWP()->ajax         = new FacetWP_Ajax();

        // integrations
        foreach ( array( 'searchwp', 'woocommerce', 'edd', 'acf' ) as $f ) {
            include( FACETWP_DIR . "/includes/integrations/{$f}/{$f}.php" );
        }

        include( FACETWP_DIR . '/includes/functions.php' );

        // hooks
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'front_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_filter( 'redirect_canonical', array( $this, 'redirect_canonical' ), 10, 2 );
    }


    /**
     * i18n support
     */
    function load_textdomain() {
        $locale = apply_filters( 'plugin_locale', get_locale(), 'fwp' );
        $mofile = WP_LANG_DIR . '/facetwp/fwp-' . $locale . '.mo';

        if ( file_exists( $mofile ) ) {
            load_textdomain( 'fwp', $mofile );
        }
        else {
            load_plugin_textdomain( 'fwp', false, dirname( FACETWP_BASENAME ) . '/languages/' );
        }
    }


    /**
     * Register the FacetWP settings page
     */
    function admin_menu() {
        add_options_page( 'FacetWP', 'FacetWP', 'manage_options', 'facetwp', array( $this, 'settings_page' ) );
    }


    /**
     * Enqueue jQuery
     */
    function front_scripts() {
        wp_enqueue_script( 'jquery' );
    }


    /**
     * Enqueue admin tooltips
     */
    function admin_scripts( $hook ) {
        if ( 'settings_page_facetwp' == $hook ) {
            wp_enqueue_style( 'media-views' );
            wp_enqueue_script( 'jquery-powertip', FACETWP_URL . '/assets/js/jquery-powertip/jquery.powertip.min.js', array( 'jquery' ), '1.2.0' );
        }
    }


    /**
     * Route to the correct edit screen
     */
    function settings_page() {
        include( FACETWP_DIR . '/templates/page-settings.php' );
    }


    /**
     * Prevent WP from redirecting FWP pager to /page/X
     */
    function redirect_canonical( $redirect_url, $requested_url ) {
        if ( false !== strpos( $redirect_url, 'fwp_paged' ) ) {
            return false;
        }
        return $redirect_url;
    }
}

new FacetWP_Init();
