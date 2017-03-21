<?php

class FacetWP_Facet_Autocomplete
{

    function __construct() {
        $this->label = __( 'Autocomplete', 'fwp' );

        // ajax
        add_action( 'wp_ajax_facetwp_autocomplete_load', array( $this, 'ajax_load' ) );
        add_action( 'wp_ajax_nopriv_facetwp_autocomplete_load', array( $this, 'ajax_load' ) );
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {

        $output = '';
        $value = (array) $params['selected_values'];
        $value = empty( $value ) ? '' : $value[0];
        $output .= '<input type="search" class="facetwp-autocomplete" value="' . $value . '" placeholder="' . __( 'Start typing...', 'fwp' ) . '" />';
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

        // like_escape was deprecated in 4.0
        $selected_values = method_exists( $wpdb, 'esc_like' ) ?
            $wpdb->esc_like( $selected_values ) :
            like_escape( $selected_values );

        if ( empty( $selected_values ) ) {
            return 'continue';
        }

        $sql = "
        SELECT DISTINCT post_id FROM {$wpdb->prefix}facetwp_index
        WHERE facet_name = '{$facet['name']}' AND facet_display_value LIKE '%$selected_values%'";
        return $wpdb->get_col( $sql );
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
    });

    wp.hooks.addFilter('facetwp/save/autocomplete', function($this, obj) {
        obj['source'] = $this.find('.facet-source').val();
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
?>
<script src="<?php echo FACETWP_URL; ?>/assets/js/jquery-autocomplete/jquery.autocomplete.min.js"></script>
<link href="<?php echo FACETWP_URL; ?>/assets/js/jquery-autocomplete/jquery.autocomplete.css" rel="stylesheet">
<script>
(function($) {
    wp.hooks.addAction('facetwp/refresh/autocomplete', function($this, facet_name) {
        var val = $this.find('.facetwp-autocomplete').val() || '';
        FWP.facets[facet_name] = val;
    });

    $(document).on('facetwp-loaded', function() {
        $('.facetwp-autocomplete').each(function() {
            var $this = $(this);
            $this.autocomplete({
                serviceUrl: ajaxurl,
                type: 'POST',
                minChars: 3,
                deferRequestBy: 200,
                showNoSuggestionNotice: true,
                noSuggestionNotice: 'No results',
                params: {
                    action: 'facetwp_autocomplete_load',
                    facet_name: $this.closest('.facetwp-facet').attr('data-name')
                }
            });
        });
    });

    $(document).on('keyup', '.facetwp-autocomplete', function(e) {
        if (13 == e.which) {
            FWP.autoload();
        }
    });

    $(document).on('click', '.facetwp-autocomplete-update', function() {
        FWP.autoload();
    });
})(jQuery);
</script>
<?php
    }


    /**
     * Load facet values via AJAX
     */
    function ajax_load() {
        global $wpdb;

        $query = esc_sql( $_POST['query'] );
        $facet_name = esc_sql( $_POST['facet_name'] );

        $sql = "
        SELECT DISTINCT facet_display_value
        FROM {$wpdb->prefix}facetwp_index
        WHERE facet_name = '$facet_name' AND facet_display_value LIKE '%$query%'
        ORDER BY facet_display_value ASC
        LIMIT 10";
        $results = $wpdb->get_results( $sql );

        $output = array();
        foreach ( $results as $result ) {
            $output[] = array(
                'value' => $result->facet_display_value,
                'data' => $result->facet_display_value,
            );
        }

        echo json_encode( array( 'suggestions' => $output ) );
        exit;
    }
}
