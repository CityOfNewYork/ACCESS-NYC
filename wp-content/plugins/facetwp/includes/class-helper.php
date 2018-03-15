<?php

final class FacetWP_Helper
{

    /* (array) The facetwp_settings option (after hooks) */
    public $settings;

    /* (array) Associative array of facet objects */
    public $facet_types;

    /* (array) Cached data sources */
    public $data_sources;


    function __construct() {
        $this->settings = $this->load_settings();
        $this->facet_types = $this->get_facet_types();
    }


    /**
     * Parse the URL hostname
     */
    function get_http_host() {
        return parse_url( get_option( 'home' ), PHP_URL_HOST );
    }


    /**
     * Get the current page URI
     */
    function get_uri() {
        $uri = $_SERVER['REQUEST_URI'];
        if ( false !== ( $pos = strpos( $uri, '?' ) ) ) {
            $uri = substr( $uri, 0, $pos );
        }
        return trim( $uri, '/' );
    }


    /**
     * Get available facet types
     */
    function get_facet_types() {
        if ( ! empty( $this->facet_types ) ) {
            return $this->facet_types;
        }

        include( FACETWP_DIR . '/includes/facets/base.php' );

        $types = array(
            'checkboxes'    => 'Facetwp_Facet_Checkboxes',
            'dropdown'      => 'Facetwp_Facet_Dropdown',
            'fselect'       => 'Facetwp_Facet_fSelect',
            'hierarchy'     => 'Facetwp_Facet_Hierarchy',
            'search'        => 'Facetwp_Facet_Search',
            'autocomplete'  => 'Facetwp_Facet_Autocomplete',
            'slider'        => 'Facetwp_Facet_Slider',
            'date_range'    => 'Facetwp_Facet_Date_Range',
            'number_range'  => 'Facetwp_Facet_Number_Range',
            'proximity'     => 'Facetwp_Facet_Proximity_Core',
            'radio'         => 'Facetwp_Facet_Radio_Core'
        );

        $facet_types = array();

        foreach ( $types as $slug => $class_name ) {
            include( FACETWP_DIR . "/includes/facets/$slug.php" );
            $facet_types[ $slug ] = new $class_name();
        }

        return apply_filters( 'facetwp_facet_types', $facet_types );
    }


    /**
     * Get settings and allow for developer hooks
     */
    function load_settings( $last_index = false ) {
        $name = $last_index ? 'facetwp_settings_last_index' : 'facetwp_settings';
        $option = get_option( $name );
        $settings = ( false !== $option ) ? json_decode( $option, true ) : array();

        if ( empty( $settings['facets'] ) ) {
            $settings['facets'] = array();
        }
        if ( empty( $settings['templates'] ) ) {
            $settings['templates'] = array();
        }
        if ( empty( $settings['settings'] ) ) {
            $settings['settings'] = array();
        }
        if ( ! isset( $settings['settings']['term_permalink'] ) ) {
            $settings['settings']['term_permalink'] = 'slug'; // Listify compat
        }
        if ( ! isset( $settings['settings']['thousands_separator'] ) ) {
            $settings['settings']['thousands_separator'] = ',';
        }
        if ( ! isset( $settings['settings']['decimal_separator'] ) ) {
            $settings['settings']['decimal_separator'] = '.';
        }
        if ( ! isset( $settings['settings']['prefix'] ) ) {
            $settings['settings']['prefix'] = 'fwp_';
        }

        // Store raw facet & template names
        $raw_names = array();

        foreach ( $settings['facets'] as $facet ) {
            $raw_names[ 'facet-' . $facet['name'] ] = false;
        }

        foreach ( $settings['templates'] as $template ) {
            $raw_names[ 'template-' . $template['name'] ] = false;
        }

        // Programmatically registered
        $facets = apply_filters( 'facetwp_facets', $settings['facets'] );
        $templates = apply_filters( 'facetwp_templates', $settings['templates'] );
        $settings['facets'] = $settings['templates'] = array();

        // Distinguish between UI and programmatic
        foreach ( $facets as $facet ) {
            $name = 'facet-' . $facet['name'];

            if ( ! isset( $raw_names[ $name ] ) ) {
                $facet['_code'] = true;
                $settings['facets'][] = $facet;
            }
            elseif ( false === $raw_names[ $name ] ) {
                $raw_names[ $name ] = true;
                $settings['facets'][] = $facet;
            }
        }

        foreach ( $templates as $template ) {
            $name = 'template-' . $template['name'];

            if ( ! isset( $raw_names[ $name ] ) ) {
                $template['_code'] = true;
                $settings['templates'][] = $template;
            }
            elseif ( false === $raw_names[ $name ] ) {
                $raw_names[ $name ] = true;
                $settings['templates'][] = $template;
            }
        }

        // Filtered settings
        return $settings;
    }


    /**
     * Get a general setting value
     *
     * @param string $name The setting name
     * @param mixed $default The default value
     * @since 1.9
     */
    function get_setting( $name, $default = '' ) {
        if ( isset( $this->settings['settings'][ $name ] ) ) {
            return $this->settings['settings'][ $name ];
        }

        return $default;
    }


    /**
     * Get an array of all facets
     * @return array
     */
    function get_facets() {
        return $this->settings['facets'];
    }


    /**
     * Get an array of all templates
     * @return array
     */
    function get_templates() {
        return $this->settings['templates'];
    }


    /**
     * Get all properties for a single facet
     * @param string $facet_name
     * @return mixed An array of facet info, or false
     */
    function get_facet_by_name( $facet_name ) {
        foreach ( $this->get_facets() as $facet ) {
            if ( $facet_name == $facet['name'] ) {
                return $facet;
            }
        }

        return false;
    }


    /**
     * Get all properties for a single template
     *
     * @param string $template_name
     * @return mixed An array of template info, or false
     */
    function get_template_by_name( $template_name ) {
        foreach ( $this->get_templates() as $template ) {
            if ( $template_name == $template['name'] ) {
                return $template;
            }
        }

        return false;
    }


    /**
     * Get an array of term information, including depth
     * @param string $taxonomy The taxonomy name
     * @return array Term information
     * @since 0.9.0
     */
    function get_term_depths( $taxonomy ) {

        $output = array();
        $parents = array();

        $terms = get_terms( $taxonomy, array( 'hide_empty' => false ) );
        if ( is_wp_error( $terms ) ) {
            return $output;
        }

        // Get term parents
        foreach ( $terms as $term ) {
            $parents[ $term->term_id ] = $term->parent;
        }

        // Build the term array
        foreach ( $terms as $term ) {
            $output[ $term->term_id ] = array(
                'term_id'       => $term->term_id,
                'name'          => $term->name,
                'slug'          => $term->slug,
                'parent_id'     => $term->parent,
                'depth'         => 0
            );

            $current_parent = $term->parent;
            while ( 0 < (int) $current_parent ) {
                $current_parent = $parents[ $current_parent ];
                $output[ $term->term_id ]['depth']++;

                // Prevent an infinite loop
                if ( 50 < $output[ $term->term_id ]['depth'] ) {
                    break;
                }
            }
        }

        return $output;
    }


    /**
     * Finish sorting the facet values
     * The results are already sorted by depth and (name OR count), we just need
     * to move the children directly below their parents
     */
    function sort_taxonomy_values( $values = array(), $orderby = 'count' ) {

        // Create an "order" sort value based on the top-level items
        $cache = array();
        foreach ( $values as $key => $val ) {
            if ( 0 == $val['depth'] ) {
                $cache[ $val['term_id'] ] = $key;
                $values[ $key ]['order'] = $key;
            }
            else {
                $new_order = $cache[ $val['parent_id'] ] . ".$key"; // dot-separated hierarchy string
                $cache[ $val['term_id'] ] = $new_order;
                $values[ $key ]['order'] = $new_order;
            }
        }

        // Sort the array based on the new "order" element
        // Since this is a dot-separated hierarchy string, treat it like version_compare
        usort( $values, function( $a, $b ) {
            return version_compare( $a['order'], $b['order'] );
        });

        return $values;
    }


    /**
     * Sanitize SQL data
     * @return mixed The sanitized value(s)
     * @since 3.0.7
     */
    function sanitize( $input ) {
        global $wpdb;

        if ( is_array( $input ) ) {
            $output = array();

            foreach ( $input as $key => $val ) {
                $output[ $key ] = $this->sanitize( $val );
            }
        }
        else {
            if ( $wpdb->dbh ) {
                if ( $wpdb->use_mysqli ) {
                    $output = mysqli_real_escape_string( $wpdb->dbh, $input );
                }
                else {
                    $output = mysql_real_escape_string( $input, $wpdb->dbh );
                }
            }
            else {
                $output = addslashes( $input );
            }
        }

        return $output;
    }


    /**
     * Does an active facet with the specified setting exist?
     * @return boolean
     * @since 1.4.0
     */
    function facet_setting_exists( $setting_name, $setting_value ) {
        foreach ( FWP()->facet->facets as $f ) {
            if ( isset( $f[ $setting_name ] ) && $f[ $setting_name ] == $setting_value ) {
                return true;
            }
        }

        return false;
    }


    /**
     * Does this facet have a setting with the specified value?
     * @return boolean
     * @since 2.3.4
     */
    function facet_is( $facet, $setting_name, $setting_value ) {
        if ( is_string( $facet ) ) {
            $facet = $this->get_facet_by_name( $facet );
        }

        if ( isset( $facet[ $setting_name ] ) && $facet[ $setting_name ] == $setting_value ) {
            return true;
        }

        return false;
    }


    /**
     * Hash a facet value if needed
     * @return string
     * @since 2.1
     */
    function safe_value( $value ) {
        $value = remove_accents( $value );

        if ( preg_match( '/[^a-z0-9_.\- ]/i', $value ) ) {
            if ( ! preg_match( '/^\d{4}-(0[1-9]|1[012])-([012]\d|3[01])/', $value ) ) {
                $value = md5( $value );
            }
        }

        $value = str_replace( ' ', '-', strtolower( $value ) );
        $value = preg_replace( '/[-]{2,}/', '-', $value );
        $value = ( 50 < strlen( $value ) ) ? md5( $value ) : $value;
        return $value;
    }


    /**
     * Properly format numbers, taking separators into account
     * @return number
     * @since 2.7.5
     */
    function format_number( $num ) {
        $sep_decimal = $this->get_setting( 'decimal_separator' );
        $sep_thousands = $this->get_setting( 'thousands_separator' );

        $num = str_replace( $sep_thousands, '', $num );
        $num = ( ',' == $sep_decimal ) ? str_replace( ',', '.', $num ) : $num;
        $num = preg_replace( '/[^0-9-.]/', '', $num );

        return $num;
    }


    /**
     * Get facet data sources
     * @return array
     * @since 2.2.1
     */
    function get_data_sources() {

        // Return cached sources
        if ( ! empty( $this->data_sources ) ) {
            return $this->data_sources;
        }

        global $wpdb;

        // Get excluded meta keys
        $excluded_fields = apply_filters( 'facetwp_excluded_custom_fields', array(
            '_edit_last',
            '_edit_lock',
        ) );

        // Get taxonomies
        $taxonomies = get_taxonomies( array(), 'object' );

        // Get custom fields
        $meta_keys = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} ORDER BY meta_key" );
        $custom_fields = array_diff( $meta_keys, $excluded_fields );

        $sources = array(
            'posts' => array(
                'label' => __( 'Posts', 'fwp' ),
                'choices' => array(
                    'post_type'         => __( 'Post Type', 'fwp' ),
                    'post_date'         => __( 'Post Date', 'fwp' ),
                    'post_modified'     => __( 'Post Modified', 'fwp' ),
                    'post_title'        => __( 'Post Title', 'fwp' ),
                    'post_author'       => __( 'Post Author', 'fwp' )
                ),
                'weight' => 10
            ),
            'taxonomies' => array(
                'label' => __( 'Taxonomies', 'fwp' ),
                'choices' => array(),
                'weight' => 20
            ),
            'custom_fields' => array(
                'label' => __( 'Custom Fields', 'fwp' ),
                'choices' => array(),
                'weight' => 30
            )
        );

        foreach ( $taxonomies as $tax ) {
            $sources['taxonomies']['choices'][ 'tax/' . $tax->name ] = $tax->labels->name;
        }

        foreach ( $custom_fields as $cf ) {
            if ( 0 !== strpos( $cf, '_oembed_' ) ) {
                $sources['custom_fields']['choices'][ 'cf/' . $cf ] = $cf;
            }
        }

        $sources = apply_filters( 'facetwp_facet_sources', $sources );

        uasort( $sources, array( $this, 'sort_by_weight' ) );

        $this->data_sources = $sources;

        return $sources;
    }


    /**
     * Sort facetwp_facet_sources by weight
     * @since 2.7.5
     */
    function sort_by_weight( $a, $b ) {
        $a['weight'] = isset( $a['weight'] ) ? $a['weight'] : 10;
        $b['weight'] = isset( $b['weight'] ) ? $b['weight'] : 10;

        if ( $a['weight'] == $b['weight'] ) {
            return 0;
        }

        return ( $a['weight'] < $b['weight'] ) ? -1 : 1;
    }


    /**
     * Grab the license key
     * @since 3.0.3
     */
    function get_license_key() {
        $license_key = defined( 'FACETWP_LICENSE_KEY' ) ? FACETWP_LICENSE_KEY : get_option( 'facetwp_license' );
        $license_key = apply_filters( 'facetwp_license_key', $license_key );
        return sanitize_text_field( trim( $license_key ) );
    }
}
