<?php

class FacetWP_Facet_Autocomplete extends FacetWP_Facet
{

    function __construct() {
        $this->label = __( 'Autocomplete', 'fwp' );

        // ajax
        add_action( 'facetwp_autocomplete_load', array( $this, 'ajax_load' ) );

        // css-based template
        add_action( 'facetwp_found_main_query', array( $this, 'template_handler' ) );

        // deprecated
        add_action( 'wp_ajax_facetwp_autocomplete_load', array( $this, 'ajax_load' ) );
        add_action( 'wp_ajax_nopriv_facetwp_autocomplete_load', array( $this, 'ajax_load' ) );
    }


    /**
     * For CSS-based templates, the "facetwp_autocomplete_load" action isn't fired
     * so we need to manually check the action
     */
    function template_handler() {
        if ( isset( $_POST['action'] ) && 'facetwp_autocomplete_load' == $_POST['action'] ) {
            $this->ajax_load();
        }
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {

        $output = '';
        $value = (array) $params['selected_values'];
        $value = empty( $value ) ? '' : stripslashes( $value[0] );
        $placeholder = isset( $params['facet']['placeholder'] ) ? $params['facet']['placeholder'] : __( 'Start typing...', 'fwp' );
        $placeholder = facetwp_i18n( $placeholder );
        $output .= '<input type="search" class="facetwp-autocomplete" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" />';
        $output .= '<input type="button" class="facetwp-autocomplete-update" value="' . __( 'Update', 'fwp' ) . '" />';
        return $output;
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $selected_values = $params['selected_values'];
        $selected_values = is_array( $selected_values ) ? $selected_values[0] : $selected_values;
        $selected_values = stripslashes( $selected_values );

        if ( empty( $selected_values ) ) {
            return 'continue';
        }

        $sql = "
        SELECT DISTINCT post_id FROM {$wpdb->prefix}facetwp_index
        WHERE facet_name = %s AND facet_display_value LIKE %s";

        $sql = $wpdb->prepare( $sql, $facet['name'], '%' . $selected_values . '%' );
        return facetwp_sql( $sql, $facet );
    }


    /**
     * Output any admin scripts
     */
    function admin_scripts() {
?>
<script>
(function($) {
    wp.hooks.addAction('facetwp/load/autocomplete', function($this, obj) {
        $this.find('.facet-source').val(obj.source);
        $this.find('.facet-placeholder').val(obj.placeholder);
    });

    wp.hooks.addFilter('facetwp/save/autocomplete', function(obj, $this) {
        obj['source'] = $this.find('.facet-source').val();
        obj['placeholder'] = $this.find('.facet-placeholder').val();
        return obj;
    });
})(jQuery);
</script>
<?php
    }


    /**
     * Output any front-end scripts
     */
    function front_scripts() {
        FWP()->display->json['no_results'] = __( 'No results', 'fwp' );
        FWP()->display->assets['jquery.autocomplete.js'] = FACETWP_URL . '/assets/vendor/jquery-autocomplete/jquery.autocomplete.min.js';
        FWP()->display->assets['jquery.autocomplete.css'] = FACETWP_URL . '/assets/vendor/jquery-autocomplete/jquery.autocomplete.css';
    }


    /**
     * Load facet values via AJAX
     */
    function ajax_load() {
        global $wpdb;

        // optimizations
        $_POST['data']['soft_refresh'] = 1;
        $_POST['data']['extras'] = array();

        // simulate a refresh
        FWP()->facet->render(
            FWP()->ajax->process_post_data()
        );

        // then grab the matching post IDs
        $post_ids = FWP()->facet->query_args['post__in'];
        $post_ids = implode( ',', $post_ids );

        $query = FWP()->helper->sanitize( $wpdb->esc_like( $_POST['query'] ) );
        $facet_name = FWP()->helper->sanitize( $_POST['facet_name'] );
        $output = array();

        if ( ! empty( $query ) && ! empty( $facet_name ) && ! empty( $post_ids ) ) {
            $sql = "
            SELECT DISTINCT facet_display_value
            FROM {$wpdb->prefix}facetwp_index
            WHERE
                facet_name = '$facet_name' AND
                facet_display_value LIKE '%$query%' AND
                post_id IN ($post_ids)
            ORDER BY facet_display_value ASC
            LIMIT 10";

            $results = $wpdb->get_results( $sql );

            foreach ( $results as $result ) {
                $output[] = array(
                    'value' => $result->facet_display_value,
                    'data' => $result->facet_display_value,
                );
            }
        }

        wp_send_json( array( 'suggestions' => $output ) );
    }


    /**
     * Output admin settings HTML
     */
    function settings_html() {
?>
        <tr>
            <td><?php _e( 'Placeholder text', 'fwp' ); ?>:</td>
            <td><input type="text" class="facet-placeholder" value="" /></td>
        </tr>
<?php
    }
}
