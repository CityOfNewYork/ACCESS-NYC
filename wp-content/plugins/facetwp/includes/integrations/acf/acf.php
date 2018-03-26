<?php

class FacetWP_Integration_ACF
{

    public $fields = array();
    public $parent_type_lookup = array();
    public $repeater_row;
    public $acf_version;


    function __construct() {
        $this->acf_version = acf()->settings['version'];

        add_filter( 'facetwp_facet_sources', array( $this, 'facet_sources' ) );
        add_filter( 'facetwp_indexer_query_args', array( $this, 'lookup_acf_fields' ) );
        add_filter( 'facetwp_indexer_post_facet', array( $this, 'index_acf_values' ), 1, 2 );
        add_filter( 'facetwp_acf_display_value', array( $this, 'index_source_other' ), 1, 2 );
    }


    /**
     * Add ACF fields to the Data Sources dropdown
     */
    function facet_sources( $sources ) {
        $fields = $this->get_fields();

        $sources['acf'] = array(
            'label' => 'Advanced Custom Fields',
            'choices' => array(),
            'weight' => 5
        );

        foreach ( $fields as $field ) {
            $field_id = $field['hierarchy'];
            $field_label = '[' . $field['group_title'] . '] ' . $field['parents'] . $field['label'];
            $sources['acf']['choices'][ "acf/$field_id" ] = $field_label;
        }

        return $sources;
    }


    /**
     * Index ACF field data
     */
    function index_acf_values( $return, $params ) {
        $defaults = $params['defaults'];
        $facet = $params['facet'];

        if ( isset( $facet['source'] ) && 'acf/' == substr( $facet['source'], 0, 4 ) ) {
            $hierarchy = explode( '/', substr( $facet['source'], 4 ) );

            // get values (for sub-fields, use the parent repeater)
            $value = get_field( $hierarchy[0], $defaults['post_id'], false );

            // handle repeater values
            if ( 1 < count( $hierarchy ) ) {

                $parent_field_key = array_shift( $hierarchy );
                $value = $this->process_field_value( $value, $hierarchy, $parent_field_key );

                // get the sub-field properties
                $sub_field = $this->get_field_object( $hierarchy[0], $defaults['post_id'] );

                foreach ( $value as $key => $val ) {
                    $this->repeater_row = $key;
                    $this->index_field_value( $val, $sub_field, $defaults );
                }
            }
            else {

                // get the field properties
                $field = $this->get_field_object( $hierarchy[0], $defaults['post_id'] );

                // index values
                $this->index_field_value( $value, $field, $defaults );
            }

            return true;
        }

        return $return;
    }


    /**
     * Hijack the "facetwp_indexer_query_args" hook to lookup the fields once
     */
    function lookup_acf_fields( $args ) {
        $this->get_fields();
        return $args;
    }


    /**
     * Grab all ACF fields
     */
    function get_fields() {
        if ( version_compare( $this->acf_version, '5.0', '>=' ) ) {
            $fields = $this->get_acf_fields_v5();
        }
        else {
            $fields = $this->get_acf_fields_v4();
        }

        return $fields;
    }


    /**
     * get_field_object() changed in ACF5
     */
    function get_field_object( $selector, $post_id ) {
        if ( version_compare( $this->acf_version, '5.0', '>=' ) ) {
            return get_field_object( $selector, $post_id, false, false );
        }
        else {
            return get_field_object( $selector, $post_id, array( 'load_value' => false ) );
        }
    }


    /**
     * Extract field values from the repeater array
     */
    function process_field_value( $value, $hierarchy, $parent_field_key ) {

        if ( ! is_array( $value ) ) {
            return array();
        }

        // vars
        $temp_val = array();
        $parent_field_type = $this->parent_type_lookup[ $parent_field_key ];

        // reduce the hierarchy array
        $field_key = array_shift( $hierarchy );

        // group
        if ( 'group' == $parent_field_type ) {
            if ( 0 == count( $hierarchy ) ) {
                $temp_val[] = $value[ $field_key ];
            }
            else {
                return $this->process_field_value( $value[ $field_key ], $hierarchy, $field_key );
            }
        }
        // repeater
        else {
            if ( 0 == count( $hierarchy ) ) {
                foreach ( $value as $val ) {
                    $temp_val[] = $val[ $field_key ];
                }
            }
            else {
                foreach ( $value as $outer ) {
                    if ( isset( $outer[ $field_key ] ) ) {
                        foreach ( $outer[ $field_key ] as $inner ) {
                            $temp_val[] = $inner;
                        }
                    }
                }

                return $this->process_field_value( $temp_val, $hierarchy, $field_key );
            }
        }

        return $temp_val;
    }


    /**
     * Handle advanced field types
     */
    function index_field_value( $value, $field, $params ) {
        $value = maybe_unserialize( $value );

        // checkboxes
        if ( 'checkbox' == $field['type'] || 'select' == $field['type'] || 'radio' == $field['type'] ) {
            if ( false !== $value ) {
                foreach ( (array) $value as $val ) {
                    $display_value = isset( $field['choices'][ $val ] ) ?
                        $field['choices'][ $val ] :
                        $val;

                    $params['facet_value'] = $val;
                    $params['facet_display_value'] = $display_value;
                    FWP()->indexer->index_row( $params );
                }
            }
        }

        // relationship
        elseif ( 'relationship' == $field['type'] || 'post_object' == $field['type'] ) {
            if ( false !== $value ) {
                foreach ( (array) $value as $val ) {
                    $params['facet_value'] = $val;
                    $params['facet_display_value'] = get_the_title( $val );
                    FWP()->indexer->index_row( $params );
                }
            }
        }

        // user
        elseif ( 'user' == $field['type'] ) {
            if ( false !== $value )  {
                foreach ( (array) $value as $val ) {
                    $user = get_user_by( 'id', $val );
                    if ( false !== $user ) {
                        $params['facet_value'] = $val;
                        $params['facet_display_value'] = $user->display_name;
                        FWP()->indexer->index_row( $params );
                    }
                }
            }
        }

        // taxonomy
        elseif ( 'taxonomy' == $field['type'] ) {
            if ( ! empty( $value ) ) {
                foreach ( (array) $value as $val ) {
                    global $wpdb;

                    $term_id = (int) $val;
                    $term = $wpdb->get_row( "SELECT name, slug FROM {$wpdb->terms} WHERE term_id = '$term_id' LIMIT 1" );
                    if ( null !== $term ) {
                        $params['facet_value'] = $term->slug;
                        $params['facet_display_value'] = $term->name;
                        $params['term_id'] = $term_id;
                        FWP()->indexer->index_row( $params );
                    }
                }
            }
        }

        // date_picker
        elseif ( 'date_picker' == $field['type'] ) {
            $formatted = $this->format_date( $value );
            $params['facet_value'] = $formatted;
            $params['facet_display_value'] = apply_filters( 'facetwp_acf_display_value', $formatted, $params );
            FWP()->indexer->index_row( $params );
        }

        // true_false
        elseif ( 'true_false' == $field['type'] ) {
            $display_value = ( 0 < (int) $value ) ? __( 'Yes', 'fwp' ) : __( 'No', 'fwp' );
            $params['facet_value'] = $value;
            $params['facet_display_value'] = $display_value;
            FWP()->indexer->index_row( $params );
        }

        // google_map
        elseif ( 'google_map' == $field['type'] ) {
            if ( isset( $value['lat'] ) && isset( $value['lng'] ) ) {
                $params['facet_value'] = $value['lat'];
                $params['facet_display_value'] = $value['lng'];
                FWP()->indexer->index_row( $params );
            }
        }

        // text
        else {
            $params['facet_value'] = $value;
            $params['facet_display_value'] = apply_filters( 'facetwp_acf_display_value', $value, $params );
            FWP()->indexer->index_row( $params );
        }
    }


    /**
     * Handle "source_other" setting
     */
    function index_source_other( $value, $params ) {
        $facet = FWP()->helper->get_facet_by_name( $params['facet_name'] );

        if ( ! empty( $facet['source_other'] ) ) {
            $hierarchy = explode( '/', substr( $facet['source_other'], 4 ) );
            $value = get_field( $hierarchy[0], $params['post_id'], false );

            // handle repeater values
            if ( 1 < count( $hierarchy ) ) {
                $parent_field_key = array_shift( $hierarchy );
                $value = $this->process_field_value( $value, $hierarchy, $parent_field_key );
                $value = $value[ $this->repeater_row ];
            }
        }

        if ( 'date_range' == $facet['type'] ) {
            $value = $this->format_date( $value );
        }

        return $value;
    }


    /**
     * We need to get field groups in ALL languages
     */
    function disable_wpml( $query ) {
        $query->set( 'suppress_filters', true );
    }


    /**
     * Format dates in YYYY-MM-DD
     */
    function format_date( $str ) {
        if ( 8 == strlen( $str ) && ctype_digit( $str ) ) {
            $str = substr( $str, 0, 4 ) . '-' . substr( $str, 4, 2 ) . '-' . substr( $str, 6, 2 );
        }

        return $str;
    }


    /**
     * Get field settings (ACF5)
     * @return array
     */
    function get_acf_fields_v5() {

        add_action( 'pre_get_posts', array( $this, 'disable_wpml' ) );
        $field_groups = acf_get_field_groups();
        remove_action( 'pre_get_posts', array( $this, 'disable_wpml' ) );

        foreach ( $field_groups as $field_group ) {
            $fields = acf_get_fields( $field_group );

            if ( ! empty( $fields ) ) {
                $this->flatten_fields( $fields, $field_group );
            }
        }

        return $this->fields;
    }


    /**
     * Get field settings (ACF4)
     * @return array
     */
    function get_acf_fields_v4() {

        include_once( dirname( __FILE__ ) . '/acf-field-group.php' );
        $class = new facetwp_acf_field_group();

        $field_groups = $class->get_field_groups( array() );

        foreach ( $field_groups as $field_group ) {
            $fields = $class->get_fields( array(), $field_group['id'] );
            $this->flatten_fields( $fields, $field_group );
        }

        return $this->fields;
    }


    /**
     * Generates a flat array of fields within a specific field group
     */
    function flatten_fields( $fields, $field_group, $hierarchy = '', $parents = '' ) {
        foreach ( $fields as $field ) {

            // append the hierarchy string
            $new_hierarchy = $hierarchy . '/' . $field['key'];

            // loop again for repeater or group fields
            if ( 'repeater' == $field['type'] || 'group' == $field['type'] ) {
                $new_parents = $parents . $field['label'] . ' &rarr; ';

                $this->parent_type_lookup[ $field['key'] ] = $field['type'];
                $this->flatten_fields( $field['sub_fields'], $field_group, $new_hierarchy, $new_parents );
            }
            else {
                $this->fields[] = array(
                    'key'           => $field['key'],
                    'name'          => $field['name'],
                    'label'         => $field['label'],
                    'hierarchy'     => trim( $new_hierarchy, '/' ),
                    'parents'       => $parents,
                    'group_title'   => $field_group['title'],
                );
            }
        }
    }
}


if ( function_exists( 'acf' ) ) {
    new FacetWP_Integration_ACF();
}
