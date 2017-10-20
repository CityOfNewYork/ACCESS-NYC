<?php

class FacetWP_Ajax
{

    /* (array) FacetWP-related GET variables */
    public $url_vars = array();

    /* (boolean) FWP template shortcode? */
    public $is_shortcode = false;

    /* (boolean) Is a FacetWP refresh? */
    public $is_refresh = false;

    /* (boolean) Initial load? */
    public $is_preload;


    function __construct() {

        // Authenticated
        if ( current_user_can( 'manage_options' ) ) {
            if ( check_ajax_referer( 'fwp_admin_nonce', 'nonce', false ) ) {
                add_action( 'wp_ajax_facetwp_save', array( $this, 'save_settings' ) );
                add_action( 'wp_ajax_facetwp_rebuild_index', array( $this, 'rebuild_index' ) );
                add_action( 'wp_ajax_facetwp_heartbeat', array( $this, 'heartbeat' ) );
                add_action( 'wp_ajax_facetwp_license', array( $this, 'license' ) );
                add_action( 'wp_ajax_facetwp_backup', array( $this, 'backup' ) );
            }
        }

        // Non-authenticated
        add_action( 'facetwp_refresh', array( $this, 'refresh' ) );
        add_action( 'wp_ajax_nopriv_facetwp_resume_index', array( $this, 'resume_index' ) );

        // Deprecated
        add_action( 'wp_ajax_facetwp_refresh', array( $this, 'refresh' ) );
        add_action( 'wp_ajax_nopriv_facetwp_refresh', array( $this, 'refresh' ) );

        // Intercept the template if needed
        $this->intercept_request();
    }


    /**
     * If AJAX and the template is "wp", return the buffered HTML
     * Otherwise, store the GET variables for later use
     */
    function intercept_request() {
        $action = isset( $_POST['action'] ) ? $_POST['action'] : '';

        // Store some variables
        $this->is_refresh = ( 'facetwp_refresh' == $action );
        $this->is_preload = ( 'facetwp_refresh' != $action );
        $prefix = FWP()->helper->get_setting( 'prefix' );
        $tpl = isset( $_POST['data']['template'] ) ? $_POST['data']['template'] : '';

        // Pageload
        if ( $this->is_preload ) {

            // Store GET variables
            foreach ( $_GET as $key => $val ) {
                if ( 0 === strpos( $key, $prefix ) ) {
                    $new_key = substr( $key, strlen( $prefix ) );
                    $new_val = stripslashes( $val );
                    if ( ! in_array( $new_key, array( 'paged', 'per_page', 'sort' ) ) ) {
                        $new_val = explode( ',', $new_val );
                    }

                    $this->url_vars[ $new_key ] = $new_val;
                }
            }

            $this->url_vars = apply_filters( 'facetwp_preload_url_vars', $this->url_vars );
        }

        if ( $this->is_preload || 'wp' == $tpl ) {
            add_action( 'pre_get_posts', array( $this, 'sacrificial_lamb' ), 998 );
            add_action( 'pre_get_posts', array( $this, 'update_query_vars' ), 999 );
        }

        if ( ! $this->is_preload && 'wp' == $tpl ) {
            add_action( 'shutdown', array( $this, 'inject_template' ), 0 );
            ob_start();
        }
    }


    function sacrificial_lamb( $query ) {
        // Fix for WP core issue #40393
    }


    /**
     * Force FacetWP to use the default WP query
     */
    function update_query_vars( $query ) {

        // Only run once
        if ( isset( $this->query_vars ) ) {
            return;
        }

        // Skip shortcode template
        if ( $this->is_shortcode ) {
            return;
        }

        // Skip admin
        if ( is_admin() && ! wp_doing_ajax() ) {
            return;
        }

        $is_main_query = ( $query->is_archive || $query->is_search || ( $query->is_main_query() && ! $query->is_singular ) );
        $is_main_query = ( wp_doing_ajax() && ! $this->is_refresh ) ? false : $is_main_query;
        $is_main_query = apply_filters( 'facetwp_is_main_query', $is_main_query, $query );

        if ( $is_main_query ) {

            // Set the flag
            $query->set( 'facetwp', true );

            // Store the default WP query vars
            $this->query_vars = $query->query_vars;

            // No URL variables
            if ( $this->is_preload && empty( $this->url_vars ) ) {
                return;
            }

            if ( $this->is_preload ) {
                $this->get_preload_data( 'wp' );
            }
            else {

                // Generate the FWP output
                $this->output = FWP()->facet->render(
                    $this->process_post_data()
                );
            }

            // Set up the updated query_vars
            $query->query_vars = FWP()->facet->query_args;
        }
    }


    /**
     * Preload the AJAX response so search engines can see it
     * @since 2.0
     */
    function get_preload_data( $template_name, $overrides = array() ) {

        if ( false === $template_name ) {
            $template_name = isset( $this->template_name ) ? $this->template_name : 'wp';
        }

        $this->template_name = $template_name;

        // Is this a template shortcode?
        $this->is_shortcode = ( 'wp' != $template_name );

        $params = array(
            'facets'        => array(),
            'template'      => $template_name,
            'http_params'   => array(
                'get' => $_GET,
                'uri' => FWP()->helper->get_uri(),
            ),
            'static_facet'  => '',
            'used_facets'   => array(),
            'soft_refresh'  => 0,
            'is_preload'    => 1,
            'is_bfcache'    => 0,
            'first_load'    => 0, // force load template
            'extras'        => array(),
            'paged'         => 1,
        );

        foreach ( $this->url_vars as $key => $val ) {
            if ( 'paged' == $key ) {
                $params['paged'] = $val;
            }
            elseif ( 'per_page' == $key ) {
                $params['extras']['per_page'] = $val;
            }
            elseif ( 'sort' == $key ) {
                $params['extras']['sort'] = $val;
            }
            else {
                $params['facets'][] = array(
                    'facet_name' => $key,
                    'selected_values' => $val,
                );
            }
        }

        // Override the defaults
        $params = array_merge( $params, $overrides );

        return FWP()->facet->render( $params );
    }


    /**
     * Inject the page HTML into the JSON response
     * We'll cherry-pick the content from the HTML using front.js
     */
    function inject_template() {
        $html = ob_get_clean();

        // Throw an error
        if ( empty( $this->output['settings'] ) ) {
            $html = __( 'FacetWP was unable to auto-detect the post listing', 'fwp' );
        }
        // Grab the <body> contents
        else {
            preg_match( "/<body(.*?)>(.*?)<\/body>/s", $html, $matches );

            if ( ! empty( $matches ) ) {
                $html = trim( $matches[2] );
            }
        }

        $this->output['template'] = $html;
        do_action( 'facetwp_inject_template', $this->output );
        echo json_encode( $this->output );
        exit;
    }


    /**
     * Save admin settings
     */
    function save_settings() {
        $settings = stripslashes( $_POST['data'] );
        $json_test = json_decode( $settings, true );

        // Check for valid JSON
        if ( isset( $json_test['settings'] ) ) {
            update_option( 'facetwp_settings', $settings );
            echo __( 'Settings saved', 'fwp' );
        }
        else {
            echo __( 'Error: invalid JSON', 'fwp' );
        }
        exit;
    }


    /**
     * Rebuild the index table
     */
    function rebuild_index() {
        FWP()->indexer->index();
        exit;
    }


    /**
     * Resume stalled indexer
     */
    function resume_index() {
        $touch = (int) FWP()->indexer->get_transient( 'touch' );
        if ( 0 < $touch && $_POST['touch'] == $touch ) {
            FWP()->indexer->index();
        }
        exit;
    }


    /**
     * Generate a $params array that can be passed directly into FWP()->facet->render()
     */
    function process_post_data() {
        $data = stripslashes_deep( $_POST['data'] );
        $facets = json_decode( $data['facets'], true );
        $extras = isset( $data['extras'] ) ? $data['extras'] : array();
        $used_facets = isset( $data['used_facets'] ) ? $data['used_facets'] : array();

        $params = array(
            'facets'            => array(),
            'template'          => $data['template'],
            'static_facet'      => $data['static_facet'],
            'used_facets'       => $used_facets,
            'http_params'       => $data['http_params'],
            'extras'            => $extras,
            'soft_refresh'      => (int) $data['soft_refresh'],
            'is_bfcache'        => (int) $data['is_bfcache'],
            'first_load'        => (int) $data['first_load'],
            'paged'             => (int) $data['paged'],
        );

        foreach ( $facets as $facet_name => $selected_values ) {
            $params['facets'][] = array(
                'facet_name'        => $facet_name,
                'selected_values'   => $selected_values,
            );
        }

        return $params;
    }


    /**
     * The AJAX facet refresh handler
     */
    function refresh() {

        global $wpdb;

        $params = $this->process_post_data();
        $output = FWP()->facet->render( $params );
        $data = stripslashes_deep( $_POST['data'] );
        $output = json_encode( $output );

        echo apply_filters( 'facetwp_ajax_response', $output, array(
            'data' => $data
        ) );

        exit;
    }


    /**
     * Keep track of indexing progress
     */
    function heartbeat() {
        echo FWP()->indexer->get_progress();
        exit;
    }


    /**
     * Import / export functionality
     */
    function backup() {
        $action_type = $_POST['action_type'];
        $output = array();

        if ( 'export' == $action_type ) {
            $items = $_POST['items'];

            if ( ! empty( $items ) ) {
                foreach ( $items as $item ) {
                    if ( 'facet' == substr( $item, 0, 5 ) ) {
                        $item_name = substr( $item, 6 );
                        $output['facets'][] = FWP()->helper->get_facet_by_name( $item_name );
                    }
                    elseif ( 'template' == substr( $item, 0, 8 ) ) {
                        $item_name = substr( $item, 9 );
                        $output['templates'][] = FWP()->helper->get_template_by_name( $item_name );
                    }
                }
            }
            echo json_encode( $output );
        }
        elseif ( 'import' == $action_type ) {
            $settings = FWP()->helper->settings;
            $import_code = json_decode( stripslashes( $_POST['import_code'] ), true );
            $overwrite = (int) $_POST['overwrite'];

            if ( empty( $import_code ) || ! is_array( $import_code ) ) {
                _e( 'Nothing to import', 'fwp' );
                exit;
            }

            $status = array(
                'imported' => array(),
                'skipped' => array(),
            );

            foreach ( $import_code as $object_type => $object_items ) {
                foreach ( $object_items as $object_item ) {
                    $is_match = false;
                    foreach ( $settings[$object_type] as $key => $settings_item ) {
                        if ( $object_item['name'] == $settings_item['name'] ) {
                            if ( $overwrite ) {
                                $settings[$object_type][$key] = $object_item;
                                $status['imported'][] = $object_item['label'];
                            }
                            else {
                                $status['skipped'][] = $object_item['label'];
                            }
                            $is_match = true;
                            break;
                        }
                    }

                    if ( ! $is_match ) {
                        $settings[$object_type][] = $object_item;
                        $status['imported'][] = $object_item['label'];
                    }
                }
            }

            update_option( 'facetwp_settings', json_encode( $settings ) );

            if ( ! empty( $status['imported'] ) ) {
                echo ' [<strong>' . __( 'Imported', 'fwp' ) . '</strong>] ' . implode( ', ', $status['imported'] );
            }
            if ( ! empty( $status['skipped'] ) ) {
                echo ' [<strong>' . __( 'Skipped', 'fwp' ) . '</strong>] ' . implode( ', ', $status['skipped'] );
            }
        }

        exit;
    }


    /**
     * License activation
     */
    function license() {
        $license = $_POST['license'];

        $request = wp_remote_post( 'http://api.facetwp.com', array(
            'body' => array(
                'action'        => 'activate',
                'slug'          => 'facetwp',
                'license'       => $license,
                'host'          => FWP()->helper->get_http_host(),
            )
        ) );

        if ( ! is_wp_error( $request ) || 200 == wp_remote_retrieve_response_code( $request ) ) {
            update_option( 'facetwp_license', $license );
            update_option( 'facetwp_activation', $request['body'] );
            echo $request['body'];
        }
        else {
            echo json_encode( array(
                'status'    => 'error',
                'message'   => __( 'Error', 'fwp' ) . ': ' . $request->get_error_message(),
            ) );
        }

        exit;
    }
}
