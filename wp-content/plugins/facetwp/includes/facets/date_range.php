<?php

class FacetWP_Facet_Date_Range
{

    function __construct() {
        $this->label = __( 'Date Range', 'fwp' );

        add_filter( 'facetwp_index_row', array( $this, 'index_row' ), 5, 2 );
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {

        $output = '';
        $value = $params['selected_values'];
        $value = empty( $value ) ? array( '', '' ) : $value;
        $fields = empty( $params['facet']['fields'] ) ? 'both' : $params['facet']['fields'];

        if ( 'both' == $fields || 'start_date' == $fields ) {
            $output .= '<input type="text" class="facetwp-date facetwp-date-min" value="' . $value[0] . '" placeholder="' . __( 'Start Date', 'fwp' ) . '" />';
        }
        if ( 'both' == $fields || 'end_date' == $fields ) {
            $output .= '<input type="text" class="facetwp-date facetwp-date-max" value="' . $value[1] . '" placeholder="' . __( 'End Date', 'fwp' ) . '" />';
        }
        return $output;
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $dates = $params['selected_values'];
        $where = '';

        // For dual ranges, find any overlap
        if ( ! empty( $facet['source_other'] ) ) {
            $start = empty( $dates[0] ) ? '0000-01-01' : $dates[0];
            $end = empty( $dates[1] ) ? '3000-12-31' : $dates[1];

            // http://stackoverflow.com/a/325964
            $where .= " AND (LEFT(facet_value, 10) <= '$end')";
            $where .= " AND (LEFT(facet_display_value, 10) >= '$start')";
        }
        // Otherwise, do a basic comparison
        else {
            if ( '' != $dates[0] ) {
                $where .= " AND LEFT(facet_value, 10) >= '" . $dates[0] . "'";
            }
            if ( '' != $dates[1] ) {
                $where .= " AND LEFT(facet_display_value, 10) <= '" . $dates[1] . "'";
            }
        }

        $sql = "
        SELECT DISTINCT post_id FROM {$wpdb->prefix}facetwp_index
        WHERE facet_name = '{$facet['name']}' $where";
        return $wpdb->get_col( $sql );
    }


    /**
     * Output any admin scripts
     */
    function admin_scripts() {
?>
<script>
(function($) {
    wp.hooks.addAction('facetwp/load/date_range', function($this, obj) {
        $this.find('.facet-source').val(obj.source);
        $this.find('.facet-source-other').val(obj.source_other);
        $this.find('.facet-date-fields').val(obj.fields);
    });

    wp.hooks.addFilter('facetwp/save/date_range', function($this, obj) {
        obj['source'] = $this.find('.facet-source').val();
        obj['source_other'] = $this.find('.facet-source-other').val();
        obj['fields'] = $this.find('.facet-date-fields').val();
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
<link href="<?php echo FACETWP_URL; ?>/assets/js/bootstrap-datepicker/datepicker.css?ver=<?php echo FACETWP_VERSION; ?>" rel="stylesheet">
<script src="<?php echo FACETWP_URL; ?>/assets/js/bootstrap-datepicker/bootstrap-datepicker.js?ver=<?php echo FACETWP_VERSION; ?>"></script>
<script>
(function($) {
    wp.hooks.addAction('facetwp/refresh/date_range', function($this, facet_name) {
        var min = $this.find('.facetwp-date-min').val() || '';
        var max = $this.find('.facetwp-date-max').val() || '';
        FWP.facets[facet_name] = ('' != min || '' != max) ? [min, max] : [];
    });

    wp.hooks.addFilter('facetwp/selections/date_range', function(output, params) {
        return params.selected_values[0] + ' - ' + params.selected_values[1];
    });

    wp.hooks.addAction('facetwp/ready', function() {
        $(document).on('facetwp-loaded', function() {
            $('.facetwp-date').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                clearBtn: true
            }).on('changeDate', function(e) {
                FWP.autoload();
            });
        });
    });
})(jQuery);
</script>
<?php
    }


    /**
     * Output admin settings HTML
     */
    function settings_html() {
        $sources = FWP()->helper->get_data_sources();
?>
        <tr>
            <td>
                <?php _e('Other data source', 'fwp'); ?>:
                <div class="facetwp-tooltip">
                    <span class="icon-question">?</span>
                    <div class="facetwp-tooltip-content"><?php _e( 'Use a separate value for the upper limit?', 'fwp' ); ?></div>
                </div>
            </td>
            <td>
                <select class="facet-source-other">
                    <option value=""><?php _e( 'None', 'fwp' ); ?></option>
                    <?php foreach ( $sources as $group ) : ?>
                    <optgroup label="<?php echo $group['label']; ?>">
                        <?php foreach ( $group['choices'] as $val => $label ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td><?php _e('Fields to show', 'fwp'); ?>:</td>
            <td>
                <select class="facet-date-fields">
                    <option value="both"><?php _e( 'Both', 'fwp' ); ?></option>
                    <option value="start_date"><?php _e( 'Start Date', 'fwp' ); ?></option>
                    <option value="end_date"><?php _e( 'End Date', 'fwp' ); ?></option>
                </select>
            </td>
        </tr>
<?php
    }


    /**
     * Index the 2nd data source
     * @since 2.1.1
     */
    function index_row( $params, $class ) {
        $facet = FWP()->helper->get_facet_by_name( $params['facet_name'] );

        if ( 'date_range' == $facet['type'] && ! empty( $facet['source_other'] ) ) {
            $other_params = $params;
            $other_params['facet_source'] = $facet['source_other'];
            $rows = $class->get_row_data( $other_params );
            $params['facet_display_value'] = $rows[0]['facet_display_value'];
        }

        return $params;
    }
}
