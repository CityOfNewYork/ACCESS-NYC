<?php

class FacetWP_Indexer
{

    /* (boolean) wp_insert_post running? */
    public $is_saving = false;

    /* (boolean) Whether to index a single post */
    public $index_all = false;

    /* (int) Number of posts to index before updating progress */
    public $chunk_size = 10;

    /* (array) Facet properties for the value being indexed */
    public $facet;


    function __construct() {
        add_action( 'save_post',                array( $this, 'save_post' ) );
        add_action( 'delete_post',              array( $this, 'delete_post' ) );
        add_action( 'edited_term',              array( $this, 'edit_term' ), 10, 3 );
        add_action( 'delete_term',              array( $this, 'delete_term' ), 10, 4 );
        add_action( 'set_object_terms',         array( $this, 'set_object_terms' ) );
        add_filter( 'wp_insert_post_parent',    array( $this, 'is_wp_insert_post' ) );
    }


    /**
     * Update the index when posts get saved
     * @since 0.1.0
     */
    function save_post( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( false !== wp_is_post_revision( $post_id ) ) {
            return;
        }

        $this->index( $post_id );
        $this->is_saving = false;
    }


    /**
     * Update the index when posts get deleted
     * @since 0.6.0
     */
    function delete_post( $post_id ) {
        global $wpdb;

        $wpdb->query( "DELETE FROM {$wpdb->prefix}facetwp_index WHERE post_id = $post_id" );
    }


    /**
     * Update the index when terms get saved
     * @since 0.6.0
     */
    function edit_term( $term_id, $tt_id, $taxonomy ) {
        global $wpdb;

        $term = get_term( $term_id, $taxonomy );

        $wpdb->query( $wpdb->prepare( "
            UPDATE {$wpdb->prefix}facetwp_index
            SET facet_value = %s, facet_display_value = %s
            WHERE facet_source = %s AND term_id = %d",
            $term->slug, $term->name, "tax/$taxonomy", $term_id
        ) );
    }


    /**
     * Update the index when terms get deleted
     * @since 0.6.0
     */
    function delete_term( $term, $tt_id, $taxonomy, $deleted_term ) {
        global $wpdb;

        $wpdb->query( "
            DELETE FROM {$wpdb->prefix}facetwp_index
            WHERE facet_source = 'tax/$taxonomy' AND term_id IN ('$term')"
        );
    }


    /**
     * We're hijacking wp_insert_post_parent
     * Prevent our set_object_terms() hook from firing within wp_insert_post
     * @since 2.2.2
     */
    function is_wp_insert_post( $post_parent ) {
        $this->is_saving = true;
        return $post_parent;
    }


    /**
     * Support for manual taxonomy associations
     * @since 0.8.0
     */
    function set_object_terms( $object_id ) {
        if ( ! $this->is_saving ) {
            $this->index( $object_id );
        }
    }


    /**
     * Rebuild the facet index
     * @param mixed $post_id The post ID (set to FALSE to re-index everything)
     */
    function index( $post_id = false ) {
        global $wpdb;

        // Index everything
        if ( empty( $post_id ) ) {

            // Prevent multiple indexing processes
            $touch = (int) $this->get_transient( 'touch' );

            if ( 0 < $touch ) {
                // Run only if the indexer is inactive or stalled
                if ( ( time() - $touch ) < 60 ) {
                    exit;
                }
            }
            else {
                // Clear table values
                $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}facetwp_index" );
            }

            // Bypass the PHP timeout
            ini_set( 'max_execution_time', 0 );

            // Index all flag
            $this->index_all = true;

            $args = array(
                'post_type'         => 'any',
                'post_status'       => 'publish',
                'posts_per_page'    => -1,
                'fields'            => 'ids',
                'orderby'           => 'ID',
            );
        }
        // Index a single post
        else {
            $args = array(
                'p'                 => $post_id,
                'post_type'         => 'any',
                'post_status'       => 'publish',
                'posts_per_page'    => 1,
                'fields'            => 'ids',
            );

            // Clear table values
            $wpdb->query( "DELETE FROM {$wpdb->prefix}facetwp_index WHERE post_id = $post_id" );
        }

        // Control which posts to index
        $args = apply_filters( 'facetwp_indexer_query_args', $args );

        // Get all facet sources
        $facets = FWP()->helper->get_facets();

        // Loop through all posts
        $query = new WP_Query( $args );
        $post_ids = (array) $query->posts;

        // Track where to resume
        $offset = 0;

        if ( $this->index_all ) {

            // Resume indexing
            if ( isset( $_POST['offset'] ) ) {
                $offset = (int) $_POST['offset'];
            }

            $transients = array(
                'num_indexed'   => $offset,
                'num_total'     => $query->found_posts,
                'touch'         => time(),
            );
            update_option( 'facetwp_transients', json_encode( $transients ) );
        }

        foreach ( $post_ids as $counter => $post_id ) {

            // Advance until we reach the offset
            if ( $counter < $offset ) {
                continue;
            }

            // If the indexer stalled, start fresh from the last valid chunk
            if ( 0 < $offset && ( $counter - $offset < $this->chunk_size ) ) {
                $wpdb->query( "DELETE FROM {$wpdb->prefix}facetwp_index WHERE post_id = $post_id" );
            }

            // Force WPML to change the language
            do_action( 'facetwp_indexer_post', array( 'post_id' => $post_id ) );

            // Loop through all facets
            foreach ( $facets as $facet ) {

                // Do not index search facets
                if ( 'search' == $facet['type'] ) {
                    continue;
                }

                $this->facet = $facet;
                $source = isset( $facet['source'] ) ? $facet['source'] : '';

                // Set default index_row() params
                $defaults = array(
                    'post_id'               => $post_id,
                    'facet_name'            => $facet['name'],
                    'facet_source'          => $source,
                    'facet_value'           => '',
                    'facet_display_value'   => '',
                    'term_id'               => 0,
                    'parent_id'             => 0,
                    'depth'                 => 0,
                );


                // Support custom facet indexing
                if ( apply_filters( 'facetwp_indexer_post_facet', false,
                    array( 'defaults' => $defaults, 'facet' => $facet ) ) ) {
                    continue;
                }

                // Get rows to insert
                $insert_data = $this->get_row_data( $defaults );

                foreach ( $insert_data as $row ) {
                    $this->index_row( $row );
                }
            }

            // Update the progress bar
            if ( $this->index_all ) {
                if ( 0 == ( ( $counter + 1 ) % $this->chunk_size ) ) {
                    $transients = array(
                        'num_indexed'   => $counter + 1,
                        'num_total'     => $this->get_transient( 'num_total' ),
                        'touch'         => time(),
                    );
                    update_option( 'facetwp_transients', json_encode( $transients ) );
                }
            }
        }

        // Indexing complete
        if ( $this->index_all ) {
            update_option( 'facetwp_transients', '' );
        }
    }


    /**
     * Get data for a table row
     * @since 2.1.1
     */
    function get_row_data( $defaults ) {
        $output = array();

        $facet = $this->facet;
        $post_id = $defaults['post_id'];
        $source = $defaults['facet_source'];

        if ( 'tax/' == substr( $source, 0, 4 ) ) {
            $taxonomy = substr( $source, 4 );
            $values = wp_get_object_terms( $post_id, $taxonomy );

            // Store the term depths
            $hierarchy = FWP()->helper->get_term_depths( $taxonomy );
            $used_terms = array();

            // Only index child terms
            $children = false;
            if ( ! empty( $facet['parent_term'] ) ) {
                $children = get_term_children( $facet['parent_term'], $taxonomy );
            }

            foreach ( $values as $value ) {

                // If "parent_term" is set, only index children
                if ( false !== $children && ! in_array( $value->term_id, $children ) ) {
                    continue;
                }

                // Handle hierarchical taxonomies
                $term_info = $hierarchy[ $value->term_id ];
                $depth = $term_info['depth'];

                // Prevent duplicate terms
                if ( isset( $used_terms[ $value->term_id ] ) ) {
                    continue;
                }
                $used_terms[ $value->term_id ] = true;

                $params = $defaults;
                $params['facet_value'] = $value->slug;
                $params['facet_display_value'] = $value->name;
                $params['term_id'] = $value->term_id;
                $params['parent_id'] = $term_info['parent_id'];
                $params['depth'] = $depth;
                $output[] = $params;

                // Automatically index implicit parents
                if ( 'hierarchy' == $facet['type'] || ( ! empty( $facet['hierarchical'] ) && 'yes' == $facet['hierarchical'] ) ) {
                    while ( $depth > 0 ) {
                        $term_id = $term_info['parent_id'];
                        $term_info = $hierarchy[ $term_id ];
                        $depth = $depth - 1;

                        if ( ! isset( $used_terms[ $term_id ] ) ) {
                            $used_terms[ $term_id ] = true;

                            $params = $defaults;
                            $params['facet_value'] = $term_info['slug'];
                            $params['facet_display_value'] = $term_info['name'];
                            $params['term_id'] = $term_id;
                            $params['parent_id'] = $term_info['parent_id'];
                            $params['depth'] = $depth;
                            $output[] = $params;
                        }
                    }
                }
            }
        }
        elseif ( 'cf/' == substr( $source, 0, 3 ) ) {
            $source_noprefix = substr( $source, 3 );
            $values = get_post_meta( $post_id, $source_noprefix, false );
            foreach ( $values as $value ) {
                if ( '' != $value ) {
                    $params = $defaults;
                    $params['facet_value'] = $value;
                    $params['facet_display_value'] = $value;
                    $output[] = $params;
                }
            }
        }
        elseif ( 'post' == substr( $source, 0, 4 ) ) {
            $post = get_post( $post_id );
            $value = $post->{$source};
            $display_value = $value;
            if ( 'post_author' == $source ) {
                $user = get_user_by( 'id', $value );
                $display_value = $user->display_name;
            }
            elseif ( 'post_type' == $source ) {
                $post_type = get_post_type_object( $value );
                if ( isset( $post_type->labels->name ) ) {
                    $display_value = $post_type->labels->name;
                }
            }

            $params = $defaults;
            $params['facet_value'] = $value;
            $params['facet_display_value'] = $display_value;
            $output[] = $params;
        }

        return $output;
    }


    /**
     * Index a facet value
     * @since 0.6.0
     */
    function index_row( $params ) {

        // Allow for custom indexing
        $params = apply_filters( 'facetwp_index_row', $params, $this );

        // Allow hooks to bypass the row insertion
        if ( is_array( $params ) ) {
            $this->insert( $params );
        }
    }


    /**
     * Save a facet value to DB
     * This can be trigged by "facetwp_index_row" to handle multiple values
     * @since 1.2.5
     */
    function insert( $params ) {
        global $wpdb;

        // Only accept scalar values
        $value = $params['facet_value'];
        if ( '' === $value || ! is_scalar( $value ) ) {
            return;
        }

        $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}facetwp_index
            (post_id, facet_name, facet_source, facet_value, facet_display_value, term_id, parent_id, depth) VALUES (%d, %s, %s, %s, %s, %d, %d, %d)",
            $params['post_id'],
            $params['facet_name'],
            $params['facet_source'],
            FWP()->helper->safe_value( $value ),
            $params['facet_display_value'],
            $params['term_id'],
            $params['parent_id'],
            $params['depth']
        ) );
    }


    /**
     * Get the indexing completion percentage
     * @return mixed The decimal percentage, or -1
     * @since 0.1.0
     */
    function get_progress() {
        $return = -1;
        $num_indexed = (int) $this->get_transient( 'num_indexed' );
        $num_total = (int) $this->get_transient( 'num_total' );
        $touch = (int) $this->get_transient( 'touch' );

        if ( 0 < $num_total ) {

            // Resume a stalled indexer
            if ( 60 < ( time() - $touch ) ) {
                $post_data = array(
                    'blocking'  => false,
                    'timeout'   => 0.02,
                    'body'      => array(
                        'action'    => 'facetwp_resume_index',
                        'offset'    => $num_indexed,
                        'touch'     => $touch
                    )
                );
                wp_remote_post( admin_url( 'admin-ajax.php' ), $post_data );
            }

            // Calculate the percent completion
            if ( $num_indexed != $num_total ) {
                $return = round( 100 * ( $num_indexed / $num_total ), 2 );
            }
        }

        return $return;
    }


    /**
     * Get indexer transient variables
     * @since 1.7.8
     */
    function get_transient( $name = false ) {
        $transients = get_option( 'facetwp_transients' );

        if ( ! empty( $transients ) ) {
            $transients = json_decode( $transients, true );
            if ( $name ) {
                return isset( $transients[ $name ] ) ? $transients[ $name ] : false;
            }

            return $transients;
        }

        return false;
    }
}
