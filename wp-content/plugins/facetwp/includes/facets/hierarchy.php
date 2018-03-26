<?php

class FacetWP_Facet_Hierarchy extends FacetWP_Facet
{

    function __construct() {
        $this->label = __( 'Hierarchy', 'fwp' );
    }


    /**
     * Load the available choices
     */
    function load_values( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $from_clause = $wpdb->prefix . 'facetwp_index f';
        $where_clause = $params['where_clause'];

        $selected_values = (array) $params['selected_values'];
        $facet_parent_id = 0;
        $output = array();

        // Orderby
        $orderby = $this->get_orderby( $facet );

        // Determine the parent_id and depth
        if ( ! empty( $selected_values[0] ) ) {

            // Get term ID from slug
            $sql = "
            SELECT t.term_id
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id AND tt.taxonomy = %s
            WHERE t.slug = %s
            LIMIT 1";

            $value = $selected_values[0];
            $taxonomy = str_replace( 'tax/', '', $facet['source'] );
            $facet_parent_id = (int) $wpdb->get_var( $wpdb->prepare( $sql, $taxonomy, $value ) );

            // Invalid term
            if ( $facet_parent_id < 1 ) {
                return array();
            }

            // Create term lookup array
            $depths = FWP()->helper->get_term_depths( $taxonomy );
            $max_depth = (int) $depths[ $facet_parent_id ]['depth'];
            $last_parent_id = $facet_parent_id;

            // Loop backwards
            for ( $i = 0; $i <= $max_depth; $i++ ) {
                $output[] = array(
                    'facet_value'           => $depths[ $last_parent_id ]['slug'],
                    'facet_display_value'   => $depths[ $last_parent_id ]['name'],
                    'depth'                 => $depths[ $last_parent_id ]['depth'] + 1,
                    'counter'               => 1, // FWP.settings.num_choices
                );

                $last_parent_id = (int) $depths[ $last_parent_id ]['parent_id'];
            }

            $output[] = array(
                'facet_value'           => '',
                'facet_display_value'   => __( 'Any', 'fwp' ),
                'depth'                 => 0,
                'counter'               => 1,
            );

            // Reverse it
            $output = array_reverse( $output );
        }

        // Update the WHERE clause
        $where_clause .= " AND parent_id = '$facet_parent_id'";

        $orderby = apply_filters( 'facetwp_facet_orderby', $orderby, $facet );
        $from_clause = apply_filters( 'facetwp_facet_from', $from_clause, $facet );
        $where_clause = apply_filters( 'facetwp_facet_where', $where_clause, $facet );

        $sql = "
        SELECT f.facet_value, f.facet_display_value, COUNT(DISTINCT f.post_id) AS counter
        FROM $from_clause
        WHERE f.facet_name = '{$facet['name']}' $where_clause
        GROUP BY f.facet_value
        ORDER BY $orderby";

        $results = $wpdb->get_results( $sql, ARRAY_A );
        $new_depth = empty( $output ) ? 0 : $output[ count( $output ) - 1 ]['depth'] + 1;

        foreach ( $results as $result ) {
            $result['depth'] = $new_depth;
            $result['is_choice'] = true;
            $output[] = $result;
        }

        return $output;
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {
        $facet = $params['facet'];
        $values = (array) $params['values'];
        $selected_values = (array) $params['selected_values'];

        $output = '';
        $num_visible = ctype_digit( $facet['count'] ) ? $facet['count'] : 10;
        $num = 0;

        if ( ! empty( $values ) ) {
            foreach ( $values as $data ) {
                $last_depth = isset( $last_depth ) ? $last_depth : $data['depth'];

                $label = esc_html( $data['facet_display_value'] );
                $is_checked = ( ! empty( $selected_values ) && $data['facet_value'] == $selected_values[0] );
                $class = $is_checked ? ' checked' : '';

                if ( $data['depth'] > $last_depth ) {
                    $output .= '<div class="facetwp-depth">';
                }

                if ( $num == $num_visible ) {
                    $output .= '<div class="facetwp-overflow facetwp-hidden">';
                }

                if ( ! $is_checked ) {
                    if ( isset( $data['is_choice'] ) ) {
                        $label .= ' <span class="facetwp-counter">(' . $data['counter'] . ')</span>';
                    }
                    else {
                        $label = '&#8249; ' . $label;
                    }
                }

                $output .= '<div class="facetwp-link' . $class . '" data-value="' . esc_attr( $data['facet_value'] ) . '">' . $label . '</div>';

                if ( isset( $data['is_choice'] ) ) {
                    $num++;
                }

                $last_depth = $data['depth'];
            }

            if ( $num_visible < $num ) {
                $output .= '</div>';
                $output .= '<a class="facetwp-toggle">' . __( 'See more', 'fwp' ) . '</a>';
                $output .= '<a class="facetwp-toggle facetwp-hidden">' . __( 'See less', 'fwp' ) . '</a>';
            }

            for ( $i = 0; $i <= $last_depth; $i++ ) {
                $output .= '</div>';
            }
        }

        return $output;
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $selected_values = $params['selected_values'];
        $selected_values = implode( "','", $selected_values );

        $sql = "
        SELECT DISTINCT post_id FROM {$wpdb->prefix}facetwp_index
        WHERE facet_name = '{$facet['name']}' AND facet_value IN ('$selected_values')";
        return facetwp_sql( $sql, $facet );
    }


    /**
     * Output any admin scripts
     */
    function admin_scripts() {
?>
<script>
(function($) {
    wp.hooks.addAction('facetwp/load/hierarchy', function($this, obj) {
        $this.find('.facet-source').val(obj.source);
        $this.find('.facet-orderby').val(obj.orderby);
        $this.find('.facet-count').val(obj.count);
    });

    wp.hooks.addFilter('facetwp/save/hierarchy', function(obj, $this) {
        obj['source'] = $this.find('.facet-source').val();
        obj['orderby'] = $this.find('.facet-orderby').val();
        obj['count'] = $this.find('.facet-count').val();
        return obj;
    });
})(jQuery);
</script>
<?php
    }


    /**
     * Output admin settings HTML
     */
    function settings_html() {
?>
        <tr>
            <td><?php _e( 'Sort by', 'fwp' ); ?>:</td>
            <td>
                <select class="facet-orderby">
                    <option value="count"><?php _e( 'Highest Count', 'fwp' ); ?></option>
                    <option value="display_value"><?php _e( 'Display Value', 'fwp' ); ?></option>
                    <option value="raw_value"><?php _e( 'Raw Value', 'fwp' ); ?></option>
                    <option value="term_order"><?php _e( 'Term Order', 'fwp' ); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <?php _e( 'Count', 'fwp' ); ?>:
                <div class="facetwp-tooltip">
                    <span class="icon-question">?</span>
                    <div class="facetwp-tooltip-content"><?php _e( 'The maximum number of facet choices to show', 'fwp' ); ?></div>
                </div>
            </td>
            <td><input type="text" class="facet-count" value="10" /></td>
        </tr>
<?php
    }
}
