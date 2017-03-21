<?php

class FacetWP_Integration_WooCommerce
{

    function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'front_scripts' ) );
        add_filter( 'facetwp_facet_sources', array( $this, 'facet_sources' ) );
        add_filter( 'facetwp_indexer_post_facet', array( $this, 'index_stock_status' ), 10, 2 );
    }


    /**
     * Run WooCommerce handlers on facetwp-refresh
     * @since 2.0.9
     */
    function front_scripts() {
        wp_enqueue_script( 'facetwp-woocommerce', FACETWP_URL . '/includes/integrations/woocommerce/woocommerce.js', array( 'jquery' ), FACETWP_VERSION );
    }


    /**
     * Add a Data Source for product availability
     * @since 2.1.4
     */
    function facet_sources( $sources ) {
        $sources['woocommerce'] = array(
            'label' => __( 'WooCommerce', 'fwp' ),
            'choices' => array(
                'woocommerce/stock_status' => __( 'Stock Status' )
            )
        );
        return $sources;
    }


    /**
     * Index each product's stock status
     * @since 2.1.4
     */
    function index_stock_status( $return, $params ) {
        $facet = $params['facet'];
        $defaults = $params['defaults'];

        if ( 'woocommerce/stock_status' == $facet['source'] ) {
            if ( 'product' == get_post_type( $defaults['post_id'] ) ) {
                WC()->api->includes();
                WC()->api->register_resources( new WC_API_Server( '/' ) );
                $response = WC()->api->WC_API_Products->get_product( $defaults['post_id'] );
                $product = $response['product'];

                if ( 'variable' == $product['type'] ) {
                    $in_stock = false;
                    foreach ( $product['variations'] as $variation ) {
                        if ( true === $variation['in_stock'] ) {
                            $in_stock = true;
                            break;
                        }
                    }
                }
                else {
                    $in_stock = $product['in_stock'];
                }

                $defaults['facet_value'] = (int) $in_stock;
                $defaults['facet_display_value'] = $in_stock ? __( 'In Stock', 'fwp' ) : __( 'Out of Stock', 'fwp' );
                FWP()->indexer->index_row( $defaults );
            }
            return true;
        }
        return $return;
    }
}


if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    new FacetWP_Integration_WooCommerce();
}
