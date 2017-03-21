<?php

class FacetWP_Facet
{

    /* (array) Data for the currently-selected facets */
    public $facets;

    /* (string) Template name */
    public $template;

    /* (array) WP_Query arguments */
    public $query_args;

    /* (array) AJAX parameters passed in */
    public $ajax_params;

    /* (array) HTTP parameters from the original page (URI, GET) */
    public $http_params;

    /* (boolean) Whether search is active */
    public $is_search = false;

    /* (array) Data for the sort box dropdown */
    public $sort_options;

    /* (array) The final WP_Query object */
    public $query;


    function __construct() {
        $this->facet_types = FWP()->helper->facet_types;
    }


    /**
     * Generate the facet output
     * @param array $params An array of arrays (see the FacetWP->refresh() method)
     * @return array
     */
    function render( $params ) {
        global $wpdb;


        $output = array(
            'facets'        => array(),
            'template'      => '',
            'settings'      => array(),
        );

        // Validate facets
        $this->facets = array();
        foreach ( $params['facets'] as $f ) {
            $facet = FWP()->helper->get_facet_by_name( $f['facet_name'] );
            if ( false !== $facet ) {
                $facet['selected_values'] = FWP()->helper->sanitize( $f['selected_values'] );
                $this->facets[] = $facet;
            }
        }

        // Set the AJAX and HTTP params
        $this->ajax_params = $params;
        $this->http_params = $params['http_params'];

        // Get the template from $helper->settings
        if ( 'wp' == $params['template'] ) {
            $this->template = array( 'name' => 'wp' );
            $query_args = FWP()->ajax->query_vars;
        }
        else {
            $this->template = FWP()->helper->get_template_by_name( $params['template'] );
            $query_args = $this->get_query_args();
        }

        // Detect search string
        if ( ! empty( $query_args['s'] ) ) {
            $this->is_search = true;
        }

        // Get the template "query" field
        $this->query_args = apply_filters( 'facetwp_query_args', $query_args, $this );

        // First load?
        $first_load = (bool) $params['first_load'];

        // Pagination
        $page = empty( $params['paged'] ) ? 1 : (int) $params['paged'];

        $this->query_args['paged'] = $page;

        // Narrow the posts based on the selected facets
        $post_ids = $this->get_filtered_post_ids();

        // Update the SQL query
        if ( ! empty( $post_ids ) ) {
            $this->query_args['post__in'] = $post_ids;
        }

        // Sort handler
        $sort_value = 'default';
        if ( ! empty( $params['extras']['sort'] ) ) {
            $sort_value = $params['extras']['sort'];
            $this->sort_options = $this->get_sort_options();
            if ( ! empty( $this->sort_options[ $sort_value ] ) ) {
                $args = $this->sort_options[ $sort_value ]['query_args'];
                $this->query_args = array_merge( $this->query_args, $args );
            }
        }

        // Sort the results by relevancy
        if ( $this->is_search && 'default' == $sort_value ) {
            $this->query_args['orderby'] = 'post__in';
        }

        // Set the default limit
        if ( empty( $this->query_args['posts_per_page'] ) ) {
            $this->query_args['posts_per_page'] = (int) get_option( 'posts_per_page' );
        }

        // Adhere to the "per page" box
        $per_page = isset( $params['extras']['per_page'] ) ? $params['extras']['per_page'] : '';
        if ( ! empty( $per_page ) && 'default' != $per_page ) {
            $this->query_args['posts_per_page'] = (int) $per_page;
        }

        // Run the WP_Query
        $this->query = new WP_Query( $this->query_args );

        // Debug
        if ( defined( 'FACETWP_DEBUG' ) && FACETWP_DEBUG ) {
            $output['settings']['debug'] = array(
                'query_args'    => $this->query_args,
                'sql'           => $this->query->request
            );
        }

        // Generate the template HTML
        // For performance gains, skip the template on pageload
        // except for hash-based URLs, since PHP can't detect hashes
        $permalink_type = FWP()->helper->get_setting( 'permalink_type' );
        if ( 'wp' != $this->template['name'] && ( ! $first_load || 'hash' == $permalink_type ) ) {
            $output['template'] = $this->get_template_html( $params['template'] );
        }

        // Static facet - the active facet's operator is "or"
        $static_facet = $params['static_facet'];

        // Calculate pager args
        $pager_args = array(
            'page'          => (int) $page,
            'per_page'      => (int) $this->query_args['posts_per_page'],
            'total_rows'    => (int) $this->query->found_posts,
            'total_pages'   => 1,
        );

        if ( 0 < $pager_args['per_page'] ) {
            $pager_args['total_pages'] = ceil( $pager_args['total_rows'] / $pager_args['per_page'] );
        }

        // Stick the pager args into the JSON response
        $output['settings']['pager'] = $pager_args;

        // Display the pagination HTML
        if ( isset( $params['extras']['pager'] ) ) {
            $output['pager'] = $this->paginate( $pager_args );
        }

        // Display the "per page" HTML
        if ( isset( $params['extras']['per_page'] ) ) {
            $output['per_page'] = $this->get_per_page_box();
        }

        // Display the counts HTML
        if ( isset( $params['extras']['counts'] ) ) {
            $output['counts'] = $this->get_result_count( $pager_args );
        }

        // Skip facet updates when sorting or paginating
        if ( 0 < $params['soft_refresh'] ) {
            return apply_filters( 'facetwp_render_output', $output, $params );
        }

        // Display the sort control
        if ( isset( $params['extras']['sort'] ) ) {
            $output['sort'] = $this->get_sort_html();
        }

        $where_clause = empty( $post_ids ) ? '' : "AND post_id IN (" . implode( ',', $post_ids ) . ")";

        // Get facet data
        foreach ( $this->facets as $the_facet ) {
            $facet_type = $the_facet['type'];
            $facet_name = $the_facet['name'];

            // Skip static facets
            if ( $static_facet == $facet_name ) {
                continue;
            }

            // Render each facet
            if ( isset( $this->facet_types[ $facet_type ] ) ) {
                $args = array(
                    'facet' => $the_facet,
                    'where_clause' => $where_clause,
                    'selected_values' => $the_facet['selected_values'],
                );

                // Load facet values if needed
                if ( method_exists( $this->facet_types[ $facet_type ], 'load_values' ) ) {
                    $args['values'] = $this->facet_types[ $facet_type ]->load_values( $args );
                }

                // Generate the facet HTML
                $html = $this->facet_types[ $facet_type ]->render( $args );
                $output['facets'][ $facet_name ] = apply_filters( 'facetwp_facet_html', $html, $args );

                // Return any JS settings
                if ( method_exists( $this->facet_types[ $facet_type ], 'settings_js' ) ) {
                    $output['settings'][ $facet_name ] = $this->facet_types[ $facet_type ]->settings_js( $args );
                }
            }
        }

        return apply_filters( 'facetwp_render_output', $output, $params );
    }


    /**
     * Get WP_Query arguments by executing the template "query" field
     * @return null
     */
    function get_query_args() {

        // remove UTF-8 non-breaking spaces
        $query = preg_replace( "/\xC2\xA0/", ' ', $this->template['query'] );
        return eval( '?>' . $query );
    }


    /**
     * Get ALL post IDs for the matching query
     * @return array An array of post IDs
     */
    function get_filtered_post_ids() {
        global $wpdb;

        // Only get relevant post IDs
        $args = $this->query_args;
        $args['fields'] = 'ids';
        $args['posts_per_page'] = -1;
        $args['paged'] = 1;

        $query = new WP_Query( $args );
        $post_ids = (array) $query->posts;

        // Allow hooks to modify the default post IDs
        $post_ids = apply_filters( 'facetwp_pre_filtered_post_ids', $post_ids, $this );

        // Determine whether we need to store unfiltered post IDs
        $store_ids = apply_filters( 'facetwp_store_unfiltered_post_ids', false );

        if ( $store_ids ) {
            FWP()->unfiltered_post_ids = $post_ids;
        }

        foreach ( $this->facets as $the_facet ) {

            // Stop looping
            if ( empty( $post_ids ) ) {
                break;
            }

            $matches = array();
            $selected_values = $the_facet['selected_values'];

            if ( empty( $selected_values ) ) {
                continue;
            }

            // Get the facet details
            $facet_type = $the_facet['type'];

            // Handle each facet
            if ( isset( $this->facet_types[ $facet_type ] ) && ! empty( $selected_values ) ) {

                $hook_params = array(
                    'facet' => $the_facet,
                    'selected_values' => $selected_values,
                );

                // Hook to support custom filter_posts() handler
                $matches = apply_filters( 'facetwp_facet_filter_posts', false, $hook_params );

                if ( false === $matches ) {
                    $matches = $this->facet_types[ $facet_type ]->filter_posts( $hook_params );
                }
            }

            // Skip this facet
            if ( 'continue' == $matches ) {
                continue;
            }

            // Store post IDs per facet
            // Required for dropdowns and checkboxes in "or" mode
            if ( $store_ids ) {
                FWP()->or_values[ $the_facet['name'] ] = $matches;
            }

            // Preserve post ID order for search facets
            if ( 'search' == $facet_type ) {
                $this->is_search = true;
                $intersected_ids = array();
                foreach ( $matches as $match ) {
                    if ( in_array( $match, $post_ids ) ) {
                        $intersected_ids[] = $match;
                    }
                }
                $post_ids = $intersected_ids;
            }
            else {
                $post_ids = array_intersect( $post_ids, $matches );
            }
        }

        // Return a zero array if no matches
        if ( empty( $post_ids ) ) {
            $post_ids = array( 0 );
        }

        // Reset any array keys
        $post_ids = array_values( $post_ids );

        return apply_filters( 'facetwp_filtered_post_ids', $post_ids, $this );
    }


    /**
     * Run the template display code
     * @return string (HTML)
     */
    function get_template_html() {
        global $post, $wp_query;

        $output = apply_filters( 'facetwp_template_html', false, $this );

        if ( false === $output ) {
            ob_start();

            // Preserve globals
            $temp_post = is_object( $post ) ? clone $post : $post;
            $temp_wp_query = is_object( $wp_query ) ? clone $wp_query : $wp_query;

            $query = $this->query;
            $wp_query = $query; // Make $query->blah() optional

            // Remove UTF-8 non-breaking spaces
            $display_code = $this->template['template'];
            $display_code = preg_replace( "/\xC2\xA0/", ' ', $display_code );

            eval( '?>' . $display_code );

            // Reset globals
            $post = $temp_post;
            $wp_query = $temp_wp_query;

            // Store buffered output
            $output = ob_get_clean();
        }

        $output = preg_replace( "/\xC2\xA0/", ' ', $output );
        return $output;
    }


    /**
     * Result count (1-10 of 234)
     * @param array $params An array with "page", "per_page", and "total_rows"
     * @return string
     */
    function get_result_count( $params = array() ) {
        $text_of = __( 'of', 'fwp' );

        $page = (int) $params['page'];
        $per_page = (int) $params['per_page'];
        $total_rows = (int) $params['total_rows'];

        if ( $per_page < $total_rows ) {
            $lower = ( 1 + ( ( $page - 1 ) * $per_page ) );
            $upper = ( $page * $per_page );
            $upper = ( $total_rows < $upper ) ? $total_rows : $upper;
            $output = "$lower-$upper $text_of $total_rows";
        }
        else {
            $lower = ( 0 < $total_rows ) ? 1 : 0;
            $upper = $total_rows;
            $output = $total_rows;
        }

        return apply_filters( 'facetwp_result_count', $output, array(
            'lower' => $lower,
            'upper' => $upper,
            'total' => $total_rows,
        ) );
    }


    /**
     * Handle sorting options
     * @return array 
     */
    function get_sort_options() {

        $options = array(
            'default' => array(
                'label' => __( 'Sort by', 'fwp' ),
                'query_args' => array()
            ),
            'title_asc' => array(
                'label' => __( 'Title (A-Z)', 'fwp' ),
                'query_args' => array(
                    'orderby' => 'title',
                    'order' => 'ASC',
                )
            ),
            'title_desc' => array(
                'label' => __( 'Title (Z-A)', 'fwp' ),
                'query_args' => array(
                    'orderby' => 'title',
                    'order' => 'DESC',
                )
            ),
            'date_desc' => array(
                'label' => __( 'Date (Newest)', 'fwp' ),
                'query_args' => array(
                    'orderby' => 'date',
                    'order' => 'DESC',
                )
            ),
            'date_asc' => array(
                'label' => __( 'Date (Oldest)', 'fwp' ),
                'query_args' => array(
                    'orderby' => 'date',
                    'order' => 'ASC',
                )
            )
        );

        return apply_filters( 'facetwp_sort_options', $options, array(
            'template_name' => $this->template['name'],
        ) );
    }


    /**
     * Display the sorting control
     * @return string (HTML)
     */
    function get_sort_html( $params = array() ) {

        if ( isset( $this->sort_options ) ) {
            $output = '<select class="facetwp-sort-select">';
            foreach ( $this->sort_options as $key => $atts ) {
                $output .= '<option value="' . $key . '">' . $atts['label'] . '</option>';
            }
            $output .= '</select>';
        }

        return apply_filters( 'facetwp_sort_html', $output, array(
            'sort_options' => $this->sort_options,
            'template_name' => $this->template['name'],
        ) );
    }


    /**
     * Pagination
     * @param array $params An array with "page", "per_page", and "total_rows"
     * @return string
     */
    function paginate( $params = array() ) {
        $defaults = array(
            'page' => 1,
            'per_page' => 10,
            'total_rows' => 1,
        );
        $params = array_merge( $defaults, $params );

        $output = '';
        $page = (int) $params['page'];
        $per_page = (int) $params['per_page'];
        $total_rows = (int) $params['total_rows'];
        $total_pages = (int) $params['total_pages'];

        // Only show pagination when > 1 page
        if ( 1 < $total_pages ) {

            $text_page      = __( 'Page', 'fwp' );
            $text_of        = __( 'of', 'fwp' );

            // "Page 5 of 150"
            $output .= '<span class="facetwp-pager-label">' . "$text_page $page $text_of $total_pages</span>";

            if ( 3 < $page ) {
                $output .= '<a class="facetwp-page first-page" data-page="1">&lt;&lt;</a>';
            }
            if ( 1 < ( $page - 10 ) ) {
                $output .= '<a class="facetwp-page" data-page="' . ($page - 10) . '">' . ($page - 10) . '</a>';
            }
            for ( $i = 2; $i > 0; $i-- ) {
                if ( 0 < ( $page - $i ) ) {
                    $output .= '<a class="facetwp-page" data-page="' . ($page - $i) . '">' . ($page - $i) . '</a>';
                }
            }

            // Current page
            $output .= '<a class="facetwp-page active" data-page="' . $page . '">' . $page . '</a>';

            for ( $i = 1; $i <= 2; $i++ ) {
                if ( $total_pages >= ( $page + $i ) ) {
                    $output .= '<a class="facetwp-page" data-page="' . ($page + $i) . '">' . ($page + $i) . '</a>';
                }
            }
            if ( $total_pages > ( $page + 10 ) ) {
                $output .= '<a class="facetwp-page" data-page="' . ($page + 10) . '">' . ($page + 10) . '</a>';
            }
            if ( $total_pages > ( $page + 2 ) ) {
                $output .= '<a class="facetwp-page last-page" data-page="' . $total_pages . '">&gt;&gt;</a>';
            }
        }

        return apply_filters( 'facetwp_pager_html', $output, array(
            'page' => $page,
            'per_page' => $per_page,
            'total_rows' => $total_rows,
            'total_pages' => $total_pages,
        ) );
    }


    /**
     * "Per Page" dropdown box
     * @return string
     */
    function get_per_page_box() {
        $options = apply_filters( 'facetwp_per_page_options', array( 10, 25, 50, 100 ) );

        $output = '<select class="facetwp-per-page-select">';
        $output .= '<option value="">' . __( 'Per page', 'fwp' ) . '</option>';
        foreach ( $options as $option ) {
            $output .= '<option value="' . $option . '">' . $option . '</option>';
        }
        $output .= '</select>';

        return apply_filters( 'facetwp_per_page_html', $output, array(
            'options' => $options,
        ) );
    }
}
