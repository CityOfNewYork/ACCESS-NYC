<?php

class FacetWP_Facet_Search extends FacetWP_Facet
{

    function __construct() {
        $this->label = __( 'Search', 'fwp' );
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {

        $output = '';
        $value = (array) $params['selected_values'];
        $value = empty( $value ) ? '' : stripslashes( $value[0] );
        $placeholder = isset( $params['facet']['placeholder'] ) ? $params['facet']['placeholder'] : __( 'Enter keywords', 'fwp' );
        $placeholder = facetwp_i18n( $placeholder );
        $output .= '<span class="facetwp-search-wrap">';
        $output .= '<i class="facetwp-btn"></i>';
        $output .= '<input type="text" class="facetwp-search" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" />';
        $output .= '</span>';
        return $output;
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {

        $facet = $params['facet'];
        $selected_values = $params['selected_values'];
        $selected_values = is_array( $selected_values ) ? $selected_values[0] : $selected_values;

        if ( empty( $selected_values ) ) {
            return 'continue';
        }

        // Default WP search
        $search_args = array(
            's' => $selected_values,
            'posts_per_page' => 200,
            'fields' => 'ids',
        );

        $search_args = apply_filters( 'facetwp_search_query_args', $search_args, $params );

        $query = new WP_Query( $search_args );

        return (array) $query->posts;
    }


    /**
     * Output any admin scripts
     */
    function admin_scripts() {
?>
<script>
(function($) {
    wp.hooks.addAction('facetwp/load/search', function($this, obj) {
        $this.find('.facet-search-engine').val(obj.search_engine);
        $this.find('.facet-placeholder').val(obj.placeholder);
        $this.find('.facet-auto-refresh').val(obj.auto_refresh);
    });

    wp.hooks.addFilter('facetwp/save/search', function(obj, $this) {
        obj['search_engine'] = $this.find('.facet-search-engine').val();
        obj['placeholder'] = $this.find('.facet-placeholder').val();
        obj['auto_refresh'] = $this.find('.facet-auto-refresh').val();
        return obj;
    });

    wp.hooks.addAction('facetwp/change/search', function($this) {
        $this.closest('.facetwp-row').find('.name-source').hide();
    });
})(jQuery);
</script>
<?php
    }


    /**
     * Output admin settings HTML
     */
    function settings_html() {
        $engines = apply_filters( 'facetwp_facet_search_engines', array() );
?>
        <tr>
            <td><?php _e('Search engine', 'fwp'); ?>:</td>
            <td>
                <select class="facet-search-engine">
                    <option value=""><?php _e( 'WP Default', 'fwp' ); ?></option>
                    <?php foreach ( $engines as $key => $label ) : ?>
                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td><?php _e( 'Placeholder text', 'fwp' ); ?>:</td>
            <td><input type="text" class="facet-placeholder" value="" /></td>
        </tr>
        <tr>
            <td>
                <?php _e('Auto refresh', 'fwp'); ?>:
                <div class="facetwp-tooltip">
                    <span class="icon-question">?</span>
                    <div class="facetwp-tooltip-content"><?php _e( 'Automatically refresh the results while typing?', 'fwp' ); ?></div>
                </div>
            </td>
            <td>
                <select class="facet-auto-refresh">
                    <option value="no"><?php _e( 'No', 'fwp' ); ?></option>
                    <option value="yes"><?php _e( 'Yes', 'fwp' ); ?></option>
                </select>
            </td>
        </tr>
<?php
    }


    /**
     * (Front-end) Attach settings to the AJAX response
     */
    function settings_js( $params ) {
        $auto_refresh = empty( $params['facet']['auto_refresh'] ) ? 'no' : $params['facet']['auto_refresh'];
        return array( 'auto_refresh' => $auto_refresh );
    }
}
