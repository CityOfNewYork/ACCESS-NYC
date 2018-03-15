<?php

class FacetWP_Facet
{

    /**
     * Grab the orderby, as needed by several facet types
     * @since 3.0.4
     */
    function get_orderby( $facet ) {
        $key = $facet['orderby'];

        // Count (default)
        $orderby = 'counter DESC, f.facet_display_value ASC';

        // Display value
        if ( 'display_value' == $key ) {
            $orderby = 'f.facet_display_value ASC';
        }
        // Raw value
        elseif ( 'raw_value' == $key ) {
            $orderby = 'f.facet_value ASC';
        }
        // Term order
        elseif ('term_order' == $key && 'tax' == substr( $facet['source'], 0, 3 ) ) {
            $term_ids = get_terms( array(
                'taxonomy' => str_replace( 'tax/', '', $facet['source'] ),
                'fields' => 'ids',
            ) );

            if ( ! empty( $term_ids ) && ! is_wp_error( $term_ids ) ) {
                $term_ids = implode( ',', $term_ids );
                $orderby = "FIELD(f.term_id, $term_ids)";
            }
        }

        // Sort by depth just in case
        $orderby = "f.depth, $orderby";

        return $orderby;
    }
}
