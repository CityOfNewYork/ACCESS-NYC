<?php

class FacetWP_Display
{

    /* (array) Facet types being used on the page */
    public $active_types = array();

    /* (array) Facets being used on the page */
    public $active_facets = array();

    /* (boolean) Whether to enable FacetWP for the current page */
    public $load_assets = false;

    /* (array) Scripts and stylesheets to enqueue */
    public $assets = array();

    /* (array) Data to pass to front-end JS */
    public $json = array();


    function __construct() {
        add_filter( 'widget_text', 'do_shortcode' );
        add_action( 'loop_start', array( $this, 'add_template_tag' ) );
        add_action( 'loop_no_results', array( $this, 'add_template_tag' ) );
        add_action( 'wp_footer', array( $this, 'front_scripts' ), 25 );
        add_shortcode( 'facetwp', array( $this, 'shortcode' ) );
    }


    /**
     * Detect the loop container if the "facetwp-template" class is missing
     */
    function add_template_tag( $wp_query ) {
        if ( true === $wp_query->get( 'facetwp' ) && did_action( 'wp_head' ) ) {
            echo "<!--fwp-loop-->\n";
        }
    }


    /**
     * Register shortcodes
     */
    function shortcode( $atts ) {
        $output = '';
        if ( isset( $atts['facet'] ) ) {
            $facet = FWP()->helper->get_facet_by_name( $atts['facet'] );

            if ( $facet ) {
                $output = '<div class="facetwp-facet facetwp-facet-' . $facet['name'] . ' facetwp-type-' . $facet['type'] . '" data-name="' . $facet['name'] . '" data-type="' . $facet['type'] . '"></div>';

                // Build list of active facet types
                $this->active_types[ $facet['type'] ] = $facet['type'];
                $this->active_facets[ $facet['name'] ] = $facet['name'];
                $this->load_assets = true;
            }
        }
        elseif ( isset( $atts['template'] ) ) {
            $template = FWP()->helper->get_template_by_name( $atts['template'] );

            if ( $template ) {
                global $wp_query;

                // Preload the template (search engine visible)
                $temp_query = $wp_query;
                $preload_data = FWP()->ajax->get_preload_data( $template['name'] );
                $wp_query = $temp_query;

                $output = '<div class="facetwp-template" data-name="' . $atts['template'] . '">';
                $output .= $preload_data['template'];
                $output .= '</div>';

                $this->load_assets = true;
            }
        }
        elseif ( isset( $atts['sort'] ) ) {
            $this->active_extras['sort'] = true;
            $output = '<div class="facetwp-sort"></div>';
        }
        elseif ( isset( $atts['selections'] ) ) {
            $output = '<div class="facetwp-selections"></div>';
        }
        elseif ( isset( $atts['counts'] ) ) {
            $this->active_extras['counts'] = true;
            $output = '<div class="facetwp-counts"></div>';
        }
        elseif ( isset( $atts['pager'] ) ) {
            $this->active_extras['pager'] = true;
            $output = '<div class="facetwp-pager"></div>';
        }
        elseif ( isset( $atts['per_page'] ) ) {
            $this->active_extras['per_page'] = true;
            $output = '<div class="facetwp-per-page"></div>';
        }

        $output = apply_filters( 'facetwp_shortcode_html', $output, $atts );

        return $output;
    }


    /**
     * Output facet scripts
     */
    function front_scripts() {

        // Not enqueued - front.js needs to load before front_scripts()
        if ( true === apply_filters( 'facetwp_load_assets', $this->load_assets ) ) {
            if ( true === apply_filters( 'facetwp_load_css', true ) ) {
                $this->assets['front.css'] = FACETWP_URL . '/assets/css/front.css';
            }

            $this->assets['front.js'] = FACETWP_URL . '/assets/js/dist/front.min.js';

            // Use the REST API?
            $ajaxurl = admin_url( 'admin-ajax.php' );
            if ( function_exists( 'get_rest_url' ) && apply_filters( 'facetwp_use_rest_api', true ) ) {
                $ajaxurl = get_rest_url() . 'facetwp/v1/refresh';
            }

            // Pass GET and URI params
            $http_params = array(
                'get' => $_GET,
                'uri' => FWP()->helper->get_uri(),
                'url_vars' => FWP()->ajax->url_vars,
            );

            // See FWP()->facet->get_query_args()
            if ( ! empty( FWP()->facet->archive_args ) ) {
                $http_params['archive_args'] = FWP()->facet->archive_args;
            }

            // Populate the FWP_JSON object
            $this->json['loading_animation'] = FWP()->helper->get_setting( 'loading_animation' );
            $this->json['prefix'] = FWP()->helper->get_setting( 'prefix' );
            $this->json['no_results_text'] = __( 'No results found', 'fwp' );
            $this->json['ajaxurl'] = $ajaxurl;
            $this->json['nonce'] = wp_create_nonce( 'wp_rest' );

            if ( apply_filters( 'facetwp_use_preloader', true ) ) {
                $this->json['preload_data'] = $this->prepare_preload_data();
            }

            ob_start();

            foreach ( $this->active_types as $type ) {
                $facet_class = FWP()->helper->facet_types[ $type ];
                if ( method_exists( $facet_class, 'front_scripts' ) ) {
                    $facet_class->front_scripts();
                }
            }

            $inline_scripts = ob_get_clean();
            $assets = apply_filters( 'facetwp_assets', $this->assets );

            foreach ( $assets as $slug => $url ) {
                $html = '<script src="{url}"></script>';

                if ( 'css' == substr( $slug, -3 ) ) {
                    $html = '<link href="{url}" rel="stylesheet">';
                }

                if ( false !== strpos( $url, 'facetwp' ) ) {
                    $url .= '?ver=' . FACETWP_VERSION;
                }

                echo str_replace( '{url}', $url, $html ) . "\n";
            }

            echo $inline_scripts;
?>
<script>
window.FWP_JSON = <?php echo json_encode( $this->json ); ?>;
window.FWP_HTTP = <?php echo json_encode( $http_params ); ?>;
</script>
<?php
        }
    }


    /**
     * On initial pageload, preload the facet data
     * and pass it client-side through the FWP_JSON object
     */
    function prepare_preload_data() {
        $overrides = array();
        $url_vars = FWP()->ajax->url_vars;

        foreach ( $this->active_facets as $name ) {
            $selected_values = isset( $url_vars[ $name ] ) ? $url_vars[ $name ] : array();

            $overrides['facets'][] = array(
                'facet_name' => $name,
                'selected_values' => $selected_values,
            );
        }

        if ( isset( $this->active_extras['counts'] ) ) {
            $overrides['extras']['counts'] = true;
        }
        if ( isset( $this->active_extras['pager'] ) ) {
            $overrides['extras']['pager'] = true;
        }
        if ( isset( $this->active_extras['per_page'] ) ) {
            $per_page = isset( $url_vars['per_page'] ) ? $url_vars['per_page'] : 'default';
            $overrides['extras']['per_page'] = $per_page;
        }
        if ( isset( $this->active_extras['sort'] ) ) {
            $sort = isset( $url_vars['sort'] ) ? $url_vars['sort'] : 'default';
            $overrides['extras']['sort'] = $sort;
        }

        $overrides['first_load'] = 1; // skip the template
        $output = FWP()->ajax->get_preload_data( false, $overrides );
        return $output;
    }
}
