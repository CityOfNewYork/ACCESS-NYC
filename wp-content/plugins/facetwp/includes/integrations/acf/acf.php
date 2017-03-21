<?php

class FacetWP_Integration_ACF
{

    public $fields = array();
    public $acf_version;


    function __construct() {
        $this->acf_version = acf()->settings['version'];

        if ( version_compare( $this->acf_version, '4.0', '>=' ) ) {
            add_filter( 'facetwp_facet_sources', array( $this, 'facet_sources' ) );
            add_filter( 'facetwp_indexer_post_facet', array( $this, 'indexer_post_facet' ), 10, 2 );
        }
    }


    /**
     * Add ACF fields to the Data Sources dropdown
     */
    function facet_sources( $sources ) {
        $sources['acf'] = array(
            'label' => 'Advanced Custom Fields',
            'choices' => array(),
        );

        // ACF 5
        if ( version_compare( $this->acf_version, '5.0', '>=' ) ) {
            $fields = $this->get_acf_fields_v5();
        }

        // ACF 4
        else {
            $fields = $this->get_acf_fields_v4();
        }

        foreach ( $fields as $field ) {
            $field_id = $field['hierarchy'];
            $field_label = $field['group_title'] . ': ' . $field['label'];
            $sources['acf']['choices'][ "acf/$field_id" ] = $field_label;
        }

        return $sources;
    }


    /**
     * Index ACF field data
     */
    function indexer_post_facet( $return, $params ) {
        $defaults = $params['defaults'];
        $facet = $params['facet'];

        if ( 'acf/' == substr( $facet['source'], 0, 4 ) ) {
            $hierarchy = explode( '/', substr( $facet['source'], 4 ) );

            // get the field properties
            $field = get_field_object( $hierarchy[0], $defaults['post_id'], array( 'load_value' => false ) );

            // get the field values
            // for repeater sub-fields, get the parent repeater field
            $value = get_field( $hierarchy[0], $defaults['post_id'], false );

            // handle repeater values
            if ( 1 < count( $hierarchy ) ) {

                array_shift( $hierarchy );
                $value = $this->process_field_value( $value, $hierarchy );

                // get the sub-field properties
                $sub_field = get_field_object( $hierarchy[0], $defaults['post_id'], array( 'load_value' => false ) );

                foreach ( $value as $val ) {
                    $this->index_field_value( $val, $sub_field, $defaults );
                }
            }
            else {
                $this->index_field_value( $value, $field, $defaults );
            }

            return true;
        }

        return $return;
    }


    /**
     * Extract field values from the repeater array
     */
    function process_field_value( $value, $hierarchy ) {

        if ( ! is_array( $value ) ) {
            return array();
        }

        // reduce the hierarchy array
        $field_key = array_shift( $hierarchy );
        $temp_val = array();

        // the values we need
        if ( 0 == count( $hierarchy ) ) {
            foreach ( $value as $val ) {
                $temp_val[] = $val[ $field_key ];
            }

            return $temp_val;
        }
        else {
            foreach ( $value as $first_row ) {
                foreach ( $first_row as $key => $second_row ) {
                    if ( $key == $field_key ) {
                        foreach ( $second_row as $third_row ) {
                            $temp_val[] = $third_row;
                        }
                    }
                }
            }

            return $this->process_field_value( $temp_val, $hierarchy );
        }
    }


    /**
     * Handle advanced field types
     */
    function index_field_value( $value, $field, $params ) {
        $value = maybe_unserialize( $value );

        // checkboxes
        if ( 'checkbox' == $field['type'] || 'select' == $field['type'] ) {
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
            $params['facet_display_value'] = $value;
            FWP()->indexer->index_row( $params );
        }
    }


    /**
     * Get field settings (ACF5)
     * @return array
     */
    function get_acf_fields_v5() {
        $field_groups = acf_get_field_groups();
        foreach ( $field_groups as $field_group ) {
            $fields = acf_get_fields( $field_group );
            $this->recursive_get_fields( $fields, $field_group, $hierarchy = '' );
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
            $this->recursive_get_fields( $fields, $field_group, $hierarchy = '' );
        }

        return $this->fields;
    }


    /**
     * Recursive handling for repeater fields
     *
     * We're storing a "hierarchy" string to figure out what
     * values we need via get_field()
     */
    function recursive_get_fields( $fields, $field_group, $hierarchy ) {
        foreach ( $fields as $field ) {

            // append the hierarchy string
            $new_hierarchy = $hierarchy . '/' . $field['key'];

            // loop again for repeater fields
            if ( 'repeater' == $field['type'] ) {
                $this->recursive_get_fields( $field['sub_fields'], $field_group, $new_hierarchy );
            }
            else {
                $this->fields[] = array(
                    'key'           => $field['key'],
                    'name'          => $field['name'],
                    'label'         => $field['label'],
                    'hierarchy'     => trim( $new_hierarchy, '/' ),
                    'group_title'   => $field_group['title'],
                );
            }
        }
    }
}


if ( function_exists( 'acf' ) ) {
    new FacetWP_Integration_ACF();
}
