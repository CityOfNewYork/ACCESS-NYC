<?php

class FacetWP_Facet_Proximity_Core
{

    /**
     * The ordered array of post IDs
     */
    public $ordered_posts = array();


    /**
     * An array containing each post ID and its distance
     */
    public $distance = array();


    function __construct() {
        $this->label = __( 'Proximity', 'fwp' );

        add_filter( 'facetwp_index_row', array( $this, 'index_latlng' ), 1, 2 );
        add_filter( 'facetwp_sort_options', array( $this, 'sort_options' ), 1, 2 );
        add_filter( 'facetwp_filtered_post_ids', array( $this, 'sort_by_distance' ), 10, 2 );
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {

        $output = '';
        $facet = $params['facet'];
        $value = $params['selected_values'];
        $unit = empty( $facet['unit'] ) ? 'mi' : $facet['unit'];

        $lat = empty( $value[0] ) ? '' : $value[0];
        $lng = empty( $value[1] ) ? '' : $value[1];
        $chosen_radius = empty( $value[2] ) ? '' : $value[2];
        $location_name = empty( $value[3] ) ? '' : urldecode( $value[3] );

        $radius_options = apply_filters( 'facetwp_proximity_radius_options', array( 10, 25, 50, 100, 250 ) );

        ob_start();
?>
        <input type="text" id="facetwp-location" value="<?php echo $location_name; ?>" placeholder="<?php _e( 'Enter location', 'fwp' ); ?>" />

        <select id="facetwp-radius">
            <?php foreach ( $radius_options as $radius ) : ?>
            <?php $selected = ( $chosen_radius == $radius ) ? ' selected' : ''; ?>
            <option value="<?php echo $radius; ?>"<?php echo $selected; ?>><?php echo "$radius $unit"; ?></option>
            <?php endforeach; ?>
        </select>

        <div style="display:none">
            <input type="text" class="facetwp-lat" value="<?php echo $lat; ?>" />
            <input type="text" class="facetwp-lng" value="<?php echo $lng; ?>" />
        </div>
<?php
        return ob_get_clean();
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $selected_values = $params['selected_values'];
        $unit = empty( $facet['unit'] ) ? 'mi' : $facet['unit'];
        $earth_radius = ( 'mi' == $unit ) ? 3959 : 6371;

        if ( empty( $selected_values ) || empty( $selected_values[0] ) ) {
            return 'continue';
        }

        $lat = (float) $selected_values[0];
        $lng = (float) $selected_values[1];
        $radius = (int) $selected_values[2];

        $sql = "
        SELECT DISTINCT post_id,
        ( $earth_radius * acos( cos( radians( $lat ) ) * cos( radians( facet_value ) ) * cos( radians( facet_display_value ) - radians( $lng ) ) + sin( radians( $lat ) ) * sin( radians( facet_value ) ) ) ) AS distance
        FROM {$wpdb->prefix}facetwp_index
        WHERE facet_name = '{$facet['name']}'
        HAVING distance < $radius
        ORDER BY distance";

        $this->ordered_posts = array();
        $this->distance = array();

        if ( apply_filters( 'facetwp_proximity_store_distance', false ) ) {
            $results = $wpdb->get_results( $sql );
            foreach ( $results as $row ) {
                $this->ordered_posts[] = $row->post_id;
                $this->distance[ $row->post_id ] = $row->distance;
            }
        }
        else {
            $this->ordered_posts = $wpdb->get_col( $sql );
        }

        return $this->ordered_posts;
    }


    /**
     * Output admin scripts
     */
    function admin_scripts() {
?>
<script>
(function($) {
    wp.hooks.addAction('facetwp/load/proximity', function($this, obj) {
        $this.find('.facet-source').val(obj.source);
        $this.find('.facet-unit').val(obj.unit);
    });

    wp.hooks.addFilter('facetwp/save/proximity', function($this, obj) {
        obj['source'] = $this.find('.facet-source').val();
        obj['unit'] = $this.find('.facet-unit').val();
        return obj;
    });
})(jQuery);
</script>
<?php
    }


    /**
     * Output front-end scripts
     */
    function front_scripts() {
        if ( apply_filters( 'facetwp_proximity_load_js', true ) ) {
?>
<script src="//maps.googleapis.com/maps/api/js?v=3.exp&amp;sensor=false&amp;libraries=places"></script>
<?php
        }

        // Pass extra options into Places Autocomplete
        $options = apply_filters( 'facetwp_proximity_autocomplete_options', array() );
?>
<script>

(function($) {
    $(document).on('facetwp-loaded', function() {
        var place;
        var options = <?php echo json_encode( $options ); ?>;
        var input = document.getElementById('facetwp-location');
        var autocomplete = new google.maps.places.Autocomplete(input, options);

        var $input = $('#facetwp-location');
        $input.wrap('<span class="location-wrap"></span>');
        $input.before('<i class="locate-me"></i>');
        $input.trigger('keyup');

        google.maps.event.addListener(autocomplete, 'place_changed', function () {
            place = autocomplete.getPlace();
            $('.facetwp-lat').val(place.geometry.location.lat());
            $('.facetwp-lng').val(place.geometry.location.lng());
            FWP.refresh();
        });
    });


    /**
     * Event handlers
     */
    $('.facetwp-type-proximity').on('click', '.locate-me', function(e) {
        var $this = $(this);
        var $input = $('#facetwp-location');
        var $facet = $input.closest('.facetwp-facet');
        var $lat = $('.facetwp-lat');
        var $lng = $('.facetwp-lng');

        // Reset
        if ($this.hasClass('reset')) {
            $facet.find('.facetwp-lat').val('');
            $facet.find('.facetwp-lng').val('');
            FWP.refresh();
            return;
        }

        // Loading icon
        $('.locate-me').addClass('loading');

        // HTML5 geolocation
        navigator.geolocation.getCurrentPosition(function(position) {
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;

            $lat.val(lat);
            $lng.val(lng);

            var geocoder = new google.maps.Geocoder();
            var latlng = {lat: parseFloat(lat), lng: parseFloat(lng)};
            geocoder.geocode({'location': latlng}, function(results, status) {
                if (status === google.maps.GeocoderStatus.OK) {
                    $input.val(results[0].formatted_address);
                }
                else {
                    $input.val('Your location');
                }
                $('.locate-me').addClass('reset');
                FWP.refresh();
            });

            $('.locate-me').removeClass('loading');
        },
        function() {
            $('.locate-me').removeClass('loading');
        });
    });

    $(document).on('keyup', '#facetwp-location', function() {
        if ('' == $(this).val()) {
            $('.locate-me').removeClass('reset');
        }
        else {
            $('.locate-me').addClass('reset');
        }
    });

    $(document).on('change', '#facetwp-radius', function() {
        if ('' != $('#facetwp-location').val()) {
            FWP.refresh();
        }
    });


    /*
     * WP-JS-Hooks
     */
    wp.hooks.addAction('facetwp/refresh/proximity', function($this, facet_name) {
        var lat = $this.find('.facetwp-lat').val();
        var lng = $this.find('.facetwp-lng').val();
        var radius = $this.find('#facetwp-radius').val();
        var location = encodeURIComponent($this.find('#facetwp-location').val());
        FWP.facets[facet_name] = ('' != lat && 'undefined' != typeof lat) ?
            [lat, lng, radius, location] : [];
    });

    wp.hooks.addFilter('facetwp/selections/proximity', function(label, params) {
        return 'Clear location';
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
            <td>
                <?php _e( 'Unit of measurement', 'fwp' ); ?>:
            </td>
            <td>
                <select class="facet-unit">
                    <option value="mi"><?php _e( 'Miles', 'fwp' ); ?></option>
                    <option value="km"><?php _e( 'Kilometers', 'fwp' ); ?></option>
                </select>
            </td>
        </tr>
<?php
    }


    /**
     * Index the coordinates
     * We expect a comma-separated "latitude, longitude"
     */
    function index_latlng( $params, $class ) {

        $facet = FWP()->helper->get_facet_by_name( $params['facet_name'] );

        if ( false !== $facet && 'proximity' == $facet['type'] ) {
            $latlng = $params['facet_value'];

            // Only handle "lat, lng" strings
            if ( is_string( $latlng ) ) {
                $latlng = trim( $latlng, '()' ); // no parentheses
                $latlng = str_replace( ' ', '', $latlng ); // no spaces

                if ( preg_match( "/^([\d.-]+),([\d.-]+)$/", $latlng ) ) {
                    $latlng = explode( ',', $latlng );
                    $params['facet_value'] = $latlng[0];
                    $params['facet_display_value'] = $latlng[1];
                }
            }
        }

        return $params;
    }


    /**
     * Add "Distance" to the sort box
     */
    function sort_options( $options, $params ) {

        $options['distance'] = array(
            'label' => __( 'Distance', 'fwp' ),
            'query_args' => array(
                'orderby' => 'post__in',
                'order' => 'ASC',
            ),
        );
        return $options;
    }


    /**
     * After the final list of post IDs has been produced,
     * sort them by distance if needed
     */
    function sort_by_distance( $post_ids, $class ) {

        $ordered_posts = FWP()->helper->facet_types['proximity']->ordered_posts;

        if ( ! empty( $ordered_posts ) ) {

            // Sort the post IDs according to distance
            $intersected_ids = array( 0 );

            foreach ( $ordered_posts as $p ) {
                if ( in_array( $p, $post_ids ) ) {
                    $intersected_ids[] = $p;
                }
            }

            $post_ids = $intersected_ids;
        }

        return $post_ids;
    }
}


/**
 * Get a post's distance
 * NOTE: SET facetwp_proximity_store_distance filter = TRUE
 */
function facetwp_get_distance( $post_id = false ) {
    global $post;

    // Get the post ID
    $post_id = ( false === $post_id ) ? $post->ID : $post_id;

    // Get the proximity class
    $facet_type = FWP()->helper->facet_types['proximity'];

    if ( isset( $facet_type->distance[ $post_id ] ) ) {
        $distance = $facet_type->distance[ $post_id ];
        return apply_filters( 'facetwp_proximity_distance_output', $distance );
    }

    return false;
}
