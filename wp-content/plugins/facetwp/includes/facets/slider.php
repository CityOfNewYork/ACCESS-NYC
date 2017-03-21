<?php

class FacetWP_Facet_Slider
{

    function __construct() {
        $this->label = __( 'Slider', 'fwp' );

        add_filter( 'facetwp_index_row', array( $this, 'index_row' ), 5, 2 );
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {

        $output = '';
        $value = $params['selected_values'];
        $output .= '<div class="facetwp-slider-wrap">';
        $output .= '<div class="facetwp-slider"></div>';
        $output .= '</div>';
        $output .= '<span class="facetwp-slider-label"></span>';
        $output .= '<div><input type="button" class="facetwp-slider-reset" value="' . __( 'Reset', 'fwp' ) . '" /></div>';
        return $output;
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $values = $params['selected_values'];
        $where = '';

        if ( !empty( $values[0] ) ) {
            $where .= " AND CAST(facet_value AS DECIMAL(10,2)) >= '{$values[0]}'";
        }
        if ( !empty( $values[1] ) ) {
            $where .= " AND CAST(facet_display_value AS DECIMAL(10,2)) <= '{$values[1]}'";
        }

        $sql = "
        SELECT DISTINCT post_id FROM {$wpdb->prefix}facetwp_index
        WHERE facet_name = '{$facet['name']}' $where";
        return $wpdb->get_col( $sql );
    }


    /**
     * (Front-end) Attach settings to the AJAX response
     */
    function settings_js( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $where_clause = $params['where_clause'];
        $selected_values = $params['selected_values'];

        // Set default slider values
        $defaults = array(
            'format' => '',
            'prefix' => '',
            'suffix' => '',
            'step' => 1,
        );
        $facet = array_merge( $defaults, $facet );

        $min = $wpdb->get_var( "SELECT facet_value FROM {$wpdb->prefix}facetwp_index WHERE facet_name = '{$facet['name']}' $where_clause ORDER BY (facet_value + 0) ASC LIMIT 1" );
        $max = $wpdb->get_var( "SELECT facet_display_value FROM {$wpdb->prefix}facetwp_index WHERE facet_name = '{$facet['name']}' $where_clause ORDER BY (facet_display_value + 0) DESC LIMIT 1" );

        $selected_min = isset( $selected_values[0] ) ? $selected_values[0] : $min;
        $selected_max = isset( $selected_values[1] ) ? $selected_values[1] : $max;

        return array(
            'range' => array(
                'min' => (float) $selected_min,
                'max' => (float) $selected_max
            ),
            'decimal_separator' => FWP()->helper->get_setting( 'decimal_separator' ),
            'thousands_separator' => FWP()->helper->get_setting( 'thousands_separator' ),
            'start' => array( $min, $max ),
            'format' => $facet['format'],
            'prefix' => $facet['prefix'],
            'suffix' => $facet['suffix'],
            'step' => $facet['step']
        );
    }


    /**
     * Output any admin scripts
     */
    function admin_scripts() {
?>
<script>
(function($) {
    wp.hooks.addAction('facetwp/load/slider', function($this, obj) {
        $this.find('.facet-source').val(obj.source);
        $this.find('.facet-source-other').val(obj.source_other);
        $this.find('.facet-prefix').val(obj.prefix);
        $this.find('.facet-suffix').val(obj.suffix);
        $this.find('.facet-format').val(obj.format);
        $this.find('.facet-step').val(obj.step);
    });

    wp.hooks.addFilter('facetwp/save/slider', function($this, obj) {
        obj['source'] = $this.find('.facet-source').val();
        obj['source_other'] = $this.find('.facet-source-other').val();
        obj['prefix'] = $this.find('.facet-prefix').val();
        obj['suffix'] = $this.find('.facet-suffix').val();
        obj['format'] = $this.find('.facet-format').val();
        obj['step'] = $this.find('.facet-step').val();
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
<link href="<?php echo FACETWP_URL; ?>/assets/js/noUiSlider/nouislider.min.css?ver=8.1.0" rel="stylesheet">
<script src="<?php echo FACETWP_URL; ?>/assets/js/noUiSlider/nouislider.min.js?ver=8.1.0"></script>
<script src="<?php echo FACETWP_URL; ?>/assets/js/nummy/nummy.min.js"></script>
<script>


FWP.used_facets = {};


(function($) {
    wp.hooks.addAction('facetwp/refresh/slider', function($this, facet_name) {
        FWP.facets[facet_name] = [];

        // The settings have already been loaded
        if ('undefined' !== typeof FWP.used_facets[facet_name]) {
            if ('undefined' !== typeof $this.find('.facetwp-slider')[0].noUiSlider) {
                FWP.facets[facet_name] = $this.find('.facetwp-slider')[0].noUiSlider.get();
            }
        }
    });

    wp.hooks.addAction('facetwp/set_label/slider', function($this) {
        var facet_name = $this.attr('data-name');
        var min = FWP.settings[facet_name]['lower'];
        var max = FWP.settings[facet_name]['upper'];
        var format = FWP.settings[facet_name]['format'];
        var opts = {
            decimal_separator: FWP.settings[facet_name]['decimal_separator'],
            thousands_separator: FWP.settings[facet_name]['thousands_separator']
        };

        if ( min === max ) {
            var label = FWP.settings[facet_name]['prefix']
                + nummy(min).format(format, opts)
                + FWP.settings[facet_name]['suffix'];
        }
        else {
            var label = FWP.settings[facet_name]['prefix']
                + nummy(min).format(format, opts)
                + FWP.settings[facet_name]['suffix']
                + ' &mdash; '
                + FWP.settings[facet_name]['prefix']
                + nummy(max).format(format, opts)
                + FWP.settings[facet_name]['suffix'];
        }
        $this.find('.facetwp-slider-label').html(label);
    });

    wp.hooks.addFilter('facetwp/selections/slider', function(output, params) {
        return params.el.find('.facetwp-slider-label').text();
    });

    $(document).on('facetwp-loaded', function() {
        $('.facetwp-slider:not(.ready)').each(function() {
            var $parent = $(this).closest('.facetwp-facet');
            var facet_name = $parent.attr('data-name');
            var opts = FWP.settings[facet_name];

            // On first load, check for slider URL variable
            if (false !== FWP_Helper.get_url_var(facet_name)) {
                FWP.used_facets[facet_name] = true;
            }

            // Fail on slider already initialized
            if ('undefined' != typeof $(this).data('options')) {
                return;
            }

            // Fail if start values are null
            if (null === FWP.settings[facet_name].start[0]) {
                return;
            }

            // Fail on invalid ranges
            if (parseFloat(opts.range.min) >= parseFloat(opts.range.max)) {
                FWP.settings[facet_name]['lower'] = opts.range.min;
                FWP.settings[facet_name]['upper'] = opts.range.max;
                wp.hooks.doAction('facetwp/set_label/slider', $parent);
                return;
            }

            // Support custom slider options
            var slider_opts = wp.hooks.applyFilters('facetwp/set_options/slider', {
                range: opts.range,
                start: opts.start,
                step: parseFloat(opts.step),
                connect: true
            }, { 'facet_name': facet_name });


            var slider = $(this)[0];
            noUiSlider.create(slider, slider_opts);
            slider.noUiSlider.on('update', function(values, handle) {
                FWP.settings[facet_name]['lower'] = values[0];
                FWP.settings[facet_name]['upper'] = values[1];
                wp.hooks.doAction('facetwp/set_label/slider', $parent);
            });
            slider.noUiSlider.on('set', function() {
                FWP.used_facets[facet_name] = true;
                FWP.static_facet = facet_name;
                FWP.autoload();
            });

            $(this).addClass('ready');
        });

        // Hide reset buttons
        $('.facetwp-type-slider').each(function() {
            var name = $(this).attr('data-name');
            var $button = $(this).find('.facetwp-slider-reset');
            $.isEmptyObject(FWP.facets[name]) ? $button.hide() : $button.show();
        });
    });

    $(document).on('click', '.facetwp-slider-reset', function() {
        var facet_name = $(this).closest('.facetwp-facet').attr('data-name');
        delete FWP.used_facets[facet_name];
        FWP.refresh();
    });
})(jQuery);
</script>
<?php
    }


    /**
     * (Admin) Output settings HTML
     */
    function settings_html() {
        $thousands = FWP()->helper->get_setting( 'thousands_separator' );
        $decimal = FWP()->helper->get_setting( 'decimal_separator' );
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
            <td>
                <?php _e('Prefix', 'fwp'); ?>:
                <div class="facetwp-tooltip">
                    <span class="icon-question">?</span>
                    <div class="facetwp-tooltip-content"><?php _e( 'Text that appears before each slider value', 'fwp' ); ?></div>
                </div>
            </td>
            <td><input type="text" class="facet-prefix" value="" /></td>
        </tr>
        <tr>
            <td>
                <?php _e('Suffix', 'fwp'); ?>:
                <div class="facetwp-tooltip">
                    <span class="icon-question">?</span>
                    <div class="facetwp-tooltip-content"><?php _e( 'Text that appears after each slider value', 'fwp' ); ?></div>
                </div>
            </td>
            <td><input type="text" class="facet-suffix" value="" /></td>
        </tr>
        <tr>
            <td>
                <?php _e('Format', 'fwp'); ?>:
                <div class="facetwp-tooltip">
                    <span class="icon-question">?</span>
                    <div class="facetwp-tooltip-content"><?php _e( 'The number format', 'fwp' ); ?></div>
                </div>
            </td>
            <td>
                <select class="facet-format">
                    <?php if ( '' != $thousands ) : ?>
                    <option value="0,0">5<?php echo $thousands; ?>280</option>
                    <option value="0,0.0">5<?php echo $thousands; ?>280<?php echo $decimal; ?>4</option>
                    <option value="0,0.00">5<?php echo $thousands; ?>280<?php echo $decimal; ?>42</option>
                    <?php endif; ?>
                    <option value="0">5280</option>
                    <option value="0.0">5280<?php echo $decimal; ?>4</option>
                    <option value="0.00">5280<?php echo $decimal; ?>42</option>
                    <option value="0a">5k</option>
                    <option value="0.0a">5<?php echo $decimal; ?>3k</option>
                    <option value="0.00a">5<?php echo $decimal; ?>28k</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <?php _e('Step', 'fwp'); ?>:
                <div class="facetwp-tooltip">
                    <span class="icon-question">?</span>
                    <div class="facetwp-tooltip-content"><?php _e( 'The amount of increase between intervals', 'fwp' ); ?> (default = 1)</div>
                </div>
            </td>
            <td><input type="text" class="facet-step" value="1" /></td>
        </tr>
<?php
    }


    /**
     * Index the 2nd data source
     * @since 2.1.1
     */
    function index_row( $params, $class ) {
        $facet = FWP()->helper->get_facet_by_name( $params['facet_name'] );

        if ( 'slider' == $facet['type'] && ! empty( $facet['source_other'] ) ) {
            $other_params = $params;
            $other_params['facet_source'] = $facet['source_other'];
            $rows = $class->get_row_data( $other_params );
            $params['facet_display_value'] = $rows[0]['facet_display_value'];
        }

        return $params;
    }
}
