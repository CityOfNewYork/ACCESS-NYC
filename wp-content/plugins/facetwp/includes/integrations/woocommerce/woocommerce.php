<?php

class FacetWP_Integration_WooCommerce
{

    public $cache = array();
    public $lookup = array();
    public $storage = array();
    public $variations = array();


    function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'front_scripts' ) );
        add_filter( 'facetwp_facet_sources', array( $this, 'facet_sources' ) );
        add_filter( 'facetwp_indexer_post_facet', array( $this, 'index_woo_values' ), 10, 2 );

        // Support WooCommerce product variations
        $is_enabled = ( 'yes' === FWP()->helper->get_setting( 'wc_enable_variations', 'no' ) );

        if ( apply_filters( 'facetwp_enable_product_variations', $is_enabled ) ) {
            add_filter( 'facetwp_indexer_post_facet_defaults', array( $this, 'force_taxonomy' ), 10, 2 );
            add_filter( 'facetwp_indexer_query_args', array( $this, 'index_variations' ) );
            add_filter( 'facetwp_index_row', array( $this, 'attribute_variations' ), 1 );
            add_filter( 'facetwp_wpdb_sql', array( $this, 'wpdb_sql' ), 10, 2 );
            add_filter( 'facetwp_wpdb_get_col', array( $this, 'wpdb_get_col' ), 10, 3 );
            add_filter( 'facetwp_filtered_post_ids', array( $this, 'process_variations' ) );
            add_filter( 'facetwp_facet_where', array( $this, 'facet_where' ), 10, 2 );
        }
    }


    /**
     * Run WooCommerce handlers on facetwp-refresh
     * @since 2.0.9
     */
    function front_scripts() {
        FWP()->display->assets['query-string.js'] = FACETWP_URL . '/assets/js/src/query-string.js';
        FWP()->display->assets['woocommerce.js'] = FACETWP_URL . '/includes/integrations/woocommerce/woocommerce.js';
    }


    /**
     * Add WooCommerce-specific data sources
     * @since 2.1.4
     */
    function facet_sources( $sources ) {
        $sources['woocommerce'] = array(
            'label' => __( 'WooCommerce', 'fwp' ),
            'choices' => array(
                'woo/price'             => __( 'Price' ),
                'woo/sale_price'        => __( 'Sale Price' ),
                'woo/regular_price'     => __( 'Regular Price' ),
                'woo/average_rating'    => __( 'Average Rating' ),
                'woo/stock_status'      => __( 'Stock Status' ),
                'woo/on_sale'           => __( 'On Sale' ),
                'woo/product_type'      => __( 'Product Type' ),
            ),
            'weight' => 5
        );

        // Move WC taxonomy choices
        foreach ( $sources['taxonomies']['choices'] as $key => $label ) {
            if ( 'tax/product_cat' == $key || 'tax/product_tag' == $key || 0 === strpos( $key, 'tax/pa_' ) ) {
                $sources['woocommerce']['choices'][ $key ] = $label;
                unset( $sources['taxonomies']['choices'][ $key ] );
            }
        }

        return $sources;
    }


    /**
     * Attributes for WC product variations are stored in postmeta
     * @since 2.7.2
     */
    function force_taxonomy( $defaults, $params ) {
        if ( 0 === strpos( $defaults['facet_source'], 'tax/pa_' ) ) {
            $post_id = (int) $defaults['post_id'];

            if ( 'product_variation' == get_post_type( $post_id ) ) {
                $defaults['facet_source'] = str_replace( 'tax/', 'cf/attribute_', $defaults['facet_source'] );
            }
        }

        return $defaults;
    }


    /**
     * Index product variations
     * @since 2.7
     */
    function index_variations( $args ) {

        // Saving a single product
        if ( ! empty( $args['p'] ) ) {
            if ( 'product' == get_post_type( $args['p'] ) ) {
                $product = wc_get_product( $args['p'] );
                if ( 'variable' == $product->get_type() ) {
                    $children = $product->get_children();
                    $args['post_type'] = array( 'product', 'product_variation' );
                    $args['post__in'] = $children;
                    $args['post__in'][] = $args['p'];
                    $args['posts_per_page'] = -1;
                    unset( $args['p'] );
                }
            }
        }
        // Force product variations to piggyback products
        else {
            $pt = (array) $args['post_type'];

            if ( in_array( 'any', $pt ) ) {
                $pt = get_post_types();
            }
            if ( in_array( 'product', $pt ) ) {
                $pt[] = 'product_variation';
            }

            $args['post_type'] = $pt;
        }

        return $args;
    }


    /**
     * When indexing product variations, attribute its parent product
     * @since 2.7
     */
    function attribute_variations( $params ) {
        $post_id = (int) $params['post_id'];

        if ( 'product_variation' == get_post_type( $post_id ) ) {
            $params['post_id'] = wp_get_post_parent_id( $post_id );
            $params['variation_id'] = $post_id;

            // Lookup the term name for variation values
            if ( 0 === strpos( $params['facet_source'], 'cf/attribute_pa_' ) ) {
                $taxonomy = str_replace( 'cf/attribute_', '', $params['facet_source'] );
                $term = get_term_by( 'slug', $params['facet_value'], $taxonomy );
                if ( false !== $term ) {
                    $params['term_id'] = $term->term_id;
                    $params['facet_display_value'] = $term->name;
                }
            }
        }
        else {
            $params['variation_id'] = $post_id;
        }

        return $params;
    }


    /**
     * Hijack filter_posts() to grab variation IDs
     * @since 2.7
     */
    function wpdb_sql( $sql, $facet ) {
        $sql = str_replace(
            'DISTINCT post_id',
            'DISTINCT post_id, GROUP_CONCAT(variation_id) AS variation_ids',
            $sql
        );

        $sql .= ' GROUP BY post_id';

        return $sql;
    }


    /**
     * Store a facet's variation IDs
     * @since 2.7
     */
    function wpdb_get_col( $result, $sql, $facet ) {
        global $wpdb;

        $facet_name = $facet['name'];
        $post_ids = $wpdb->get_col( $sql, 0 ); // arrays of product IDs
        $variations = $wpdb->get_col( $sql, 1 ); // variation IDs as arrays of comma-separated strings

        foreach ( $post_ids as $index => $post_id ) {
            $variations_array = explode( ',', $variations[ $index ] );
            $type = in_array( $post_id, $variations_array ) ? 'products' : 'variations';

            if ( isset( $this->cache[ $facet_name ][ $type ] ) ) {
                $temp = $this->cache[ $facet_name ][ $type ];
                $this->cache[ $facet_name ][ $type ] = array_merge( $temp, $variations_array );
            }
            else {
                $this->cache[ $facet_name ][ $type ] = $variations_array;
            }
        }

        return $result;
    }


    /**
     * We need lookup arrays for both products and variations
     * @since 2.7.1
     */
    function generate_lookup_array( $post_ids ) {
        global $wpdb;

        $output = array();

        if ( ! empty( $post_ids ) ) {
            $sql = "
            SELECT DISTINCT post_id, variation_id
            FROM {$wpdb->prefix}facetwp_index
            WHERE post_id IN (" . implode( ',', $post_ids ) . ")";
            $results = $wpdb->get_results( $sql );

            foreach ( $results as $result ) {
                $output['get_variations'][ $result->post_id ][] = $result->variation_id;
                $output['get_product'][ $result->variation_id ] = $result->post_id;
            }
        }

        return $output;
    }


    /**
     * Determine valid variation IDs
     * @since 2.7
     */
    function process_variations( $post_ids ) {
        if ( empty( $this->cache ) ) {
            return $post_ids;
        }

        $this->lookup = $this->generate_lookup_array( $post_ids );

        // Loop through each facet's data
        foreach ( $this->cache as $facet_name => $groups ) {
            $this->storage[ $facet_name ] = array();

            // Create an array of variation IDs
            foreach ( $groups as $type => $ids ) { // products or variations
                $this->storage[ $facet_name ] = array_merge( $this->storage[ $facet_name ], $ids );

                // Lookup variation IDs for each product
                if ( 'products' == $type ) {
                    foreach ( $ids as $id ) {
                        if ( ! empty( $this->lookup['get_variations'][ $id ] ) ) {
                            $this->storage[ $facet_name ] = array_merge( $this->storage[ $facet_name ], $this->lookup['get_variations'][ $id ] );
                        }
                    }
                }
            }
        }

        $result = $this->calculate_variations();
        $this->variations = $result['variations'];
        $post_ids = array_intersect( $post_ids, array_keys( $result['products'] ) );
        $post_ids = empty( $post_ids ) ? array( 0 ) : $post_ids;
        return $post_ids;
    }


    /**
     * Calculate variation IDs
     * @param mixed $facet_name Facet name to ignore, or FALSE
     * @return array Associative array of product IDs + variation IDs
     * @since 2.8
     */
    function calculate_variations( $facet_name = false ) {

        $new = true;
        $final_products = array();
        $final_variations = array();

        // Intersect product + variation IDs across facets
        foreach ( $this->storage as $name => $variation_ids ) {

            // Skip facets in "OR" mode
            if ( $facet_name === $name ) {
                continue;
            }

            $final_variations = ( $new ) ? $variation_ids : array_intersect( $final_variations, $variation_ids );
            $new = false;
        }

        // Lookup each variation's product ID
        foreach ( $final_variations as $variation_id ) {
            if ( isset( $this->lookup['get_product'][ $variation_id ] ) ) {
                $final_products[ $this->lookup['get_product'][ $variation_id ] ] = true; // prevent duplicates
            }
        }

        // Append product IDs to the variations array
        $final_variations = array_merge( $final_variations, array_keys( $final_products ) );
        $final_variations = array_unique( $final_variations );

        return array(
            'products' => $final_products,
            'variations' => $final_variations
        );
    }


    /**
     * Apply variation IDs to load_values() method
     * @since 2.7
     */
    function facet_where( $where_clause, $facet ) {

        // Support facets in "OR" mode
        if ( FWP()->helper->facet_is( $facet, 'operator', 'or' ) ) {
            $result = $this->calculate_variations( $facet['name'] );
            $variations = $result['variations'];
        }
        else {
            $variations = $this->variations;
        }

        if ( ! empty( $variations ) ) {
            $where_clause .= ' AND variation_id IN (' . implode( ',', $variations ) . ')';
        }

        return $where_clause;
    }


    /**
     * Index WooCommerce-specific values
     * @since 2.1.4
     */
    function index_woo_values( $return, $params ) {
        $facet = $params['facet'];
        $defaults = $params['defaults'];
        $post_id = (int) $defaults['post_id'];
        $post_type = get_post_type( $post_id );

        // Index out of stock products?
        $index_all = ( 'yes' === FWP()->helper->get_setting( 'wc_index_all', 'no' ) );
        $index_all = apply_filters( 'facetwp_index_all_products', $index_all );

        if ( ! $index_all && ( 'product' == $post_type || 'product_variation' == $post_type ) ) {
            $product = wc_get_product( $post_id );
            if ( ! $product || ! $product->is_in_stock() ) {
                return true; // skip
            }
        }

        if ( 'product' != $post_type || empty( $facet['source'] ) ) {
            return $return;
        }

        // Ignore product attributes with "Used for variations" ticked
        if ( 0 === strpos( $facet['source'], 'tax/pa_' ) ) {
            $product = wc_get_product( $post_id );

            if ( $product->is_type( 'variable' ) ) {
                $attrs = $product->get_attributes();
                $attr_name = str_replace( 'tax/', '', $facet['source'] );
                if ( isset( $attrs[ $attr_name ] ) && 1 === $attrs[ $attr_name ]['is_variation'] ) {
                    return true; // skip
                }
            }
        }

        // Custom woo fields
        if ( 0 === strpos( $facet['source'], 'woo' ) ) {
            $product = wc_get_product( $post_id );

            // Price
            if ( 'woo/price' == $facet['source'] ) {
                $price = $product->get_price();
                $defaults['facet_value'] = $price;
                $defaults['facet_display_value'] = $price;
                FWP()->indexer->index_row( $defaults );
            }

            // Sale Price
            elseif ( 'woo/sale_price' == $facet['source'] ) {
                $price = $product->get_sale_price();
                $defaults['facet_value'] = $price;
                $defaults['facet_display_value'] = $price;
                FWP()->indexer->index_row( $defaults );
            }

            // Regular Price
            elseif ( 'woo/regular_price' == $facet['source'] ) {
                $price = $product->get_regular_price();
                $defaults['facet_value'] = $price;
                $defaults['facet_display_value'] = $price;
                FWP()->indexer->index_row( $defaults );
            }

            // Average Rating
            elseif ( 'woo/average_rating' == $facet['source'] ) {
                $rating = $product->get_average_rating();
                $defaults['facet_value'] = $rating;
                $defaults['facet_display_value'] = $rating;
                FWP()->indexer->index_row( $defaults );
            }

            // Stock Status
            elseif ( 'woo/stock_status' == $facet['source'] ) {
                $in_stock = $product->is_in_stock();
                $defaults['facet_value'] = (int) $in_stock;
                $defaults['facet_display_value'] = $in_stock ? __( 'In Stock', 'fwp' ) : __( 'Out of Stock', 'fwp' );
                FWP()->indexer->index_row( $defaults );
            }

            // On Sale
            elseif ( 'woo/on_sale' == $facet['source'] ) {
                if ( $product->is_on_sale() ) {
                    $defaults['facet_value'] = 1;
                    $defaults['facet_display_value'] = __( 'On Sale', 'fwp' );
                    FWP()->indexer->index_row( $defaults );
                }
            }

            // Product Type
            elseif ( 'woo/product_type' == $facet['source'] ) {
                $type = $product->get_type();
                $defaults['facet_value'] = $type;
                $defaults['facet_display_value'] = $type;
                FWP()->indexer->index_row( $defaults );
            }

            return true; // skip
        }

        return $return;
    }
}


if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    new FacetWP_Integration_WooCommerce();
}
