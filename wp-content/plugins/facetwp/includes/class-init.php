<?php

class FacetWP_Init
{

    function __construct() {
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }


    /**
     * Initialize classes and WP hooks
     */
    function init() {

        // i18n
        $this->load_textdomain();

        // is_plugin_active
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        // api 
        include( FACETWP_DIR . '/includes/api/fetch.php' );
        include( FACETWP_DIR . '/includes/api/refresh.php' );

        // update checks
        if ( is_admin() ) {
            include( FACETWP_DIR . '/includes/class-updater.php' );
            include( FACETWP_DIR . '/includes/libraries/github-updater.php' );
        }

        // core
        include( FACETWP_DIR . '/includes/class-helper.php' );
        include( FACETWP_DIR . '/includes/class-ajax.php' );
        include( FACETWP_DIR . '/includes/class-renderer.php' );
        include( FACETWP_DIR . '/includes/class-diff.php' );
        include( FACETWP_DIR . '/includes/class-indexer.php' );
        include( FACETWP_DIR . '/includes/class-display.php' );
        include( FACETWP_DIR . '/includes/class-overrides.php' );
        include( FACETWP_DIR . '/includes/class-settings-admin.php' );
        include( FACETWP_DIR . '/includes/class-upgrade.php' );
        include( FACETWP_DIR . '/includes/functions.php' );

        new FacetWP_Upgrade();
        new FacetWP_Overrides();
        new FacetWP_API_Fetch();

        FWP()->helper       = new FacetWP_Helper();
        FWP()->facet        = new FacetWP_Renderer();
        FWP()->diff         = new FacetWP_Diff();
        FWP()->indexer      = new FacetWP_Indexer();
        FWP()->display      = new FacetWP_Display();
        FWP()->ajax         = new FacetWP_Ajax();

        // integrations
        include( FACETWP_DIR . '/includes/integrations/searchwp/searchwp.php' );
        include( FACETWP_DIR . '/includes/integrations/woocommerce/woocommerce.php' );
        include( FACETWP_DIR . '/includes/integrations/edd/edd.php' );
        include( FACETWP_DIR . '/includes/integrations/acf/acf.php' );

        // hooks
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'front_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_filter( 'redirect_canonical', array( $this, 'redirect_canonical' ), 10, 2 );
        add_filter( 'plugin_action_links_facetwp/index.php', array( $this, 'plugin_action_links' ) );
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
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'jquery-powertip', FACETWP_URL . '/assets/vendor/jquery-powertip/jquery.powertip.min.js', array( 'jquery' ), '1.2.0' );
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
        if ( false !== strpos( $redirect_url, FWP()->helper->get_setting( 'prefix' ) . 'paged' ) ) {
            return false;
        }
        return $redirect_url;
    }


    /**
     * Add "Settings" link to plugin listing page
     */
    function plugin_action_links( $links ) {
        $settings_link = admin_url( 'options-general.php?page=facetwp' );
        $settings_link = '<a href=" ' . $settings_link . '">' . __( 'Settings', 'fwp' )  . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }


    /**
     * Notify users to install necessary integrations
     */
    function admin_notices() {
        if ( apply_filters( 'facetwp_dismiss_notices', false ) ) {
            return;
        }

        $reqs = array(
            'WPML' => array(
                'is_active' => defined( 'ICL_SITEPRESS_VERSION' ),
                'addon' => 'facetwp-wpml/facetwp-wpml.php',
                'slug' => 'wpml'
            ),
            'Polylang' => array(
                'is_active' => function_exists( 'pll_register_string' ),
                'addon' => 'facetwp-polylang/index.php',
                'slug' => 'polylang'
            ),
            'Relevanssi' => array(
                'is_active' => function_exists( 'relevanssi_search' ),
                'addon' => 'facetwp-relevanssi/facetwp-relevanssi.php',
                'slug' => 'relevanssi'
            )
        );

        $addon = __( 'integration add-on', 'fwp' );
        $message = __( 'To use FacetWP with %s, please install the %s, then re-index.', 'fwp' );

        foreach ( $reqs as $req_name => $req ) {
            if ( $req['is_active'] && ! is_plugin_active( $req['addon'] ) ) {
                $link = sprintf( '<a href="https://facetwp.com/add-ons/%s/" target="_blank">%s</a>', $req['slug'], $addon );
                echo '<div class="error"><p>' . sprintf( $message, $req_name, $link ) . '</p></div>';
            }
        }
    }
}

$this->init = new FacetWP_Init();
