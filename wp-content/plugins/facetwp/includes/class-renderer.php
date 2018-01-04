<?php

class FacetWP_Renderer
{

    /* (array) Data for the currently-selected facets */
    public $facets;

    /* (string) Template name */
    public $template;

    /* (array) WP_Query arguments */
    public $query_args;

    /* (string) MySQL WHERE clause passed to each facet */
    public $where_clause = '';

    /* (array) AJAX parameters passed in */
    public $ajax_params;

    /* (array) HTTP parameters from the original page (URI, GET) */
    public $http_params;

    /* (boolean) Whether search is active */
    public $is_search = false;

    /* (array) Data for the sort box dropdown */
    public $sort_options;

    /* (array) Cache preloaded facet values */
    public $preloaded_values;

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

        // Hook params
        $params = apply_filters( 'facetwp_render_params', $params );

        // First ajax refresh?
        $first_load = (bool) $params['first_load'];
        $is_bfcache = (bool) $params['is_bfcache'];

        // Initial pageload?
        $this->is_preload = isset( $params['is_preload'] );

        // Set the AJAX and HTTP params
        $this->ajax_params = $params;
        $this->http_params = $params['http_params'];

        // Validate facets
        $this->facets = array();
        foreach ( $params['facets'] as $f ) {
            $name = $f['facet_name'];
            $facet = FWP()->helper->get_facet_by_name( $name );
            if ( $facet ) {

                // Support the "facetwp_preload_url_vars" hook
                if ( $first_load && empty( $f['selected_values'] ) && ! empty( $this->http_params['url_vars'][ $name ] ) ) {
                    $f['selected_values'] = $this->http_params['url_vars'][ $name ];
                }

                $facet['selected_values'] = esc_sql( $f['selected_values'] );
                $this->facets[ $name ] = $facet;
            }
        }

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

        // Run the query once (prevent duplicate queries when preloading)
        if ( empty( $this->query_args ) ) {

            // Pagination
            $page = empty( $params['paged'] ) ? 1 : (int) $params['paged'];

            // Get the template "query" field
            $this->query_args = apply_filters( 'facetwp_query_args', $query_args, $this );

            $this->query_args['paged'] = $page;

            // Narrow the posts based on the selected facets
            $post_ids = $this->get_filtered_post_ids();

            // Update the SQL query
            if ( ! empty( $post_ids ) ) {
                $this->query_args['post__in'] = $post_ids;
                $this->where_clause = "AND post_id IN (" . implode( ',', $post_ids ) . ")";
            }

            // Sort handler
            $sort_value = 'default';
            $this->sort_options = $this->get_sort_options();
            if ( ! empty( $params['extras']['sort'] ) ) {
                $sort_value = $params['extras']['sort'];
                if ( ! empty( $this->sort_options[ $sort_value ] ) ) {
                    $args = $this->sort_options[ $sort_value ]['query_args'];
                    $this->query_args = array_merge( $this->query_args, $args );
                }
            }

            // Sort the results by relevancy
            $use_relevancy = apply_filters( 'facetwp_use_search_relevancy', true, $this );
            if ( $this->is_search && $use_relevancy && 'default' == $sort_value && empty( $this->http_params['get']['orderby'] ) ) {
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
        }

        // Debug
        if ( 'on' == FWP()->helper->get_setting( 'debug_mode', 'off' ) ) {
            $output['settings']['debug'] = array(
                'query_args'    => $this->query_args,
                'sql'           => $this->query->request,
                'facets'        => $this->facets,
                'template'      => $this->template,
            );
        }

        // Generate the template HTML
        // For performance gains, skip the template on pageload
        if ( 'wp' != $this->template['name'] ) {
            if ( ! $first_load || $is_bfcache || apply_filters( 'facetwp_template_force_load', false ) ) {
                $output['template'] = $this->get_template_html( $params['template'] );
            }
        }

        // Static facet - the active facet's operator is "or"
        $static_facet = $params['static_facet'];
        $used_facets = $params['used_facets'];

        // Calculate pager args
        $pager_args = array(
            'page'          => (int) $this->query_args['paged'],
            'per_page'      => (int) $this->query_args['posts_per_page'],
            'total_rows'    => (int) $this->query->found_posts,
            'total_pages'   => 1,
        );

        if ( 0 < $pager_args['per_page'] ) {
            $pager_args['total_pages'] = ceil( $pager_args['total_rows'] / $pager_args['per_page'] );
        }

        // Stick the pager args into the JSON response
        $output['settings']['pager'] = $pager_args;

        // Set the num_choices array
        $output['settings']['num_choices'] = array();

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

        // Get facet data
        foreach ( $this->facets as $facet_name => $the_facet ) {
            $facet_type = $the_facet['type'];

            if ( ! isset( $this->facet_types[ $facet_type ] ) ) {
                continue;
            }

            // Get facet labels
            $output['settings']['labels'][ $facet_name ] = facetwp_i18n( $the_facet['label'] );

            // Skip static facets
            if ( $static_facet == $facet_name ) {
                continue;
            }

            // Skip used facets
            if ( isset( $used_facets[ $facet_name ] ) ) {
                continue;
            }

            $args = array(
                'facet' => $the_facet,
                'where_clause' => $this->where_clause,
                'selected_values' => $the_facet['selected_values'],
            );

            // Load facet values if needed
            if ( method_exists( $this->facet_types[ $facet_type ], 'load_values' ) ) {

                // Grab preloaded values if available
                if ( isset( $this->preloaded_values[ $facet_name ] ) ) {
                    $args['values'] = $this->preloaded_values[ $facet_name ];
                }
                else {
                    $args['values'] = $this->facet_types[ $facet_type ]->load_values( $args );

                    if ( $this->is_preload ) {
                        $this->preloaded_values[ $facet_name ] = $args['values'];
                    }
                }
            }

            // Filter the render args
            $args = apply_filters( 'facetwp_facet_render_args', $args );

            // Return the number of available choices
            if ( isset( $args['values'] ) ) {
                $num_choices = 0;
                $is_ghost = FWP()->helper->facet_is( $the_facet, 'ghosts', 'yes' );

                foreach ( $args['values'] as $choice ) {
                    if ( isset( $choice['counter'] ) && ( 0 < $choice['counter'] || $is_ghost ) ) {
                        $num_choices++;
                    }
                }

                $output['settings']['num_choices'][ $facet_name ] = $num_choices;
            }

            // Generate the facet HTML
            $html = $this->facet_types[ $facet_type ]->render( $args );
            $output['facets'][ $facet_name ] = apply_filters( 'facetwp_facet_html', $html, $args );

            // Return any JS settings
            if ( method_exists( $this->facet_types[ $facet_type ], 'settings_js' ) ) {
                $output['settings'][ $facet_name ] = $this->facet_types[ $facet_type ]->settings_js( $args );
            }
        }

        return apply_filters( 'facetwp_render_output', $output, $params );
    }


    /**
     * Get WP_Query arguments by executing the template "query" field
     * @return null
     */
    function get_query_args() {

        $defaults = array();

        // Allow templates to piggyback archives
        if ( apply_filters( 'facetwp_template_use_archive', false ) ) {
            $main_query = $GLOBALS['wp_the_query'];

            // Initial pageload
            if ( $main_query->is_archive ) {
                if ( $main_query->is_category ) {
                    $defaults['cat'] = $main_query->get( 'cat' );
                }
                elseif ( $main_query->is_tag ) {
                    $defaults['tag_id'] = $main_query->get( 'tag_id' );
                }
                elseif ( $main_query->is_tax ) {
                    $defaults['taxonomy'] = $main_query->get( 'taxonomy' );
                    $defaults['term'] = $main_query->get( 'term' );
                }

                $this->archive_args = $defaults;
            }
            // Subsequent ajax requests
            elseif ( ! empty( $this->http_params['archive_args'] ) ) {
                foreach ( $this->http_params['archive_args'] as $key => $val ) {
                    if ( in_array( $key, array( 'cat', 'tag_id', 'taxonomy', 'term' ) ) ) {
                        $defaults[ $key ] = $val;
                    }
                }
            }
        }

        // remove UTF-8 non-breaking spaces
        $query_args = preg_replace( "/\xC2\xA0/", ' ', $this->template['query'] );
        $query_args = (array) eval( '?>' . $query_args );

        // Merge the two arrays
        return array_merge( $defaults, $query_args );
    }


    /**
     * Get ALL post IDs for the matching query
     * @return array An array of post IDs
     */
    function get_filtered_post_ids() {
        global $wpdb;

        // Only get relevant post IDs
        $args = array_merge( $this->query_args, array(
            'paged' => 1,
            'posts_per_page' => -1,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'cache_results' => false,
            'no_found_rows' => true,
            'fields' => 'ids',
        ) );

        $query = new WP_Query( $args );
        $post_ids = (array) $query->posts;

        // Allow hooks to modify the default post IDs
        $post_ids = apply_filters( 'facetwp_pre_filtered_post_ids', $post_ids, $this );

        // Determine whether we need to store unfiltered post IDs
        $store_ids = apply_filters( 'facetwp_store_unfiltered_post_ids', false );

        // Store post IDs on pageload (since we don't know yet which facets to use)
        if ( $store_ids || $this->is_preload ) {
            FWP()->unfiltered_post_ids = $post_ids;
        }

        foreach ( $this->facets as $facet_name => $the_facet ) {
            $facet_type = $the_facet['type'];

            // Stop looping
            if ( empty( $post_ids ) ) {
                break;
            }

            $matches = array();
            $selected_values = $the_facet['selected_values'];

            if ( empty( $selected_values ) ) {
                continue;
            }

            // Handle each facet
            if ( isset( $this->facet_types[ $facet_type ] ) ) {

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

            // Force array
            $matches = (array) $matches;

            // Store post IDs per facet
            // Required for dropdowns and checkboxes in "or" mode
            if ( $store_ids || $this->is_preload ) {
                FWP()->or_values[ $facet_name ] = $matches;
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
