<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Database_Models_Submission
 */
final class NF_Database_Models_Submission
{
    protected $_id = '';

    protected $_status = '';

    protected $_user_id = '';

    protected $_form_id = '';

    protected $_seq_num = '';

    protected $_sub_date = '';

    protected $_mod_date = '';

    protected $_field_values = array();

    protected $_extra_values = array();

    public function __construct( $id = '', $form_id = '' )
    {
        $this->_id = $id;
        $this->_form_id = $form_id;

        if( $this->_id ){
            $sub = get_post( $this->_id );
            $this->_status = $sub->post_status;
            $this->_user_id = $sub->post_author;
            $this->_sub_date = $sub->post_date;
            $this->_mod_date = $sub->post_modified;
        }

        if( $this->_id && ! $this->_form_id ){
            $this->_form_id = get_post_meta( $this->_id, '_form_id', TRUE );
        }

        if( $this->_id && $this->_form_id ){
            $this->_seq_num = get_post_meta( $this->_id, '_seq_num', TRUE );
        }
    }

    /**
     * Get Submission ID
     *
     * @return int
     */
    public function get_id()
    {
        return intval( $this->_id );
    }

    public function get_status()
    {
        return $this->_status;
    }

    public function get_user()
    {
        return get_user_by( 'id', $this->_user_id );
    }

    public function get_form_id()
    {
        return intval( $this->_form_id );
    }

    public function get_form_title()
    {
        $form = Ninja_Forms()->form( $this->_form_id )->get();
        return $form->get_setting( 'title' );
    }

    public function get_seq_num()
    {
        return intval( $this->_seq_num );
    }

    public function get_sub_date( $format = 'm/d/Y' )
    {
        return date( $format, strtotime( $this->_sub_date ) );
    }

    public function get_mod_date( $format = 'm/d/Y' )
    {
        return date( $format, strtotime( $this->_mod_date ) );
    }

    /**
     * Get Field Value
     *
     * Returns a single submission value by field ID or field key.
     *
     * @param int|string $field_ref
     * @return string
     */
    public function get_field_value( $field_ref )
    {
        $field_id = ( is_numeric( $field_ref ) ) ? $field_ref : $this->get_field_id_by_key( $field_ref );

        $field = '_field_' . $field_id;

        if( isset( $this->_field_values[ $field ] ) ) return $this->_field_values[ $field ];

        $this->_field_values[ $field ] = get_post_meta($this->_id, $field, TRUE);
        $this->_field_values[ $field_ref ] = get_post_meta($this->_id, $field, TRUE);

        return $this->_field_values[ $field ];
    }

    /**
     * Get Field Values
     *
     * @return array|mixed
     */
    public function get_field_values()
    {
        if( ! empty( $this->_field_values ) ) return $this->_field_values;

        $field_values = get_post_meta( $this->_id, '' );

        foreach( $field_values as $field_id => $field_value ){
            $this->_field_values[ $field_id ] = implode( ', ', $field_value );

            if( 0 === strpos( $field_id, '_field_' ) ){
                $field_id = substr( $field_id, 7 );
            }

            if( ! is_numeric( $field_id ) ) continue;

            $field = Ninja_Forms()->form()->get_field( $field_id );
            $key = $field->get_setting( 'key' );
            if( $key ) {
                $this->_field_values[ $key ] = implode(', ', $field_value);
            }
        }

        return $this->_field_values;
    }

    /**
     * Update Field Value
     *
     * @param $field_ref
     * @param $value
     * @return $this
     */
    public function update_field_value( $field_ref, $value )
    {
        $field_id = ( is_numeric( $field_ref ) ) ? $field_ref : $this->get_field_id_by_key( $field_ref );

        $this->_field_values[ $field_id ] = WPN_Helper::kses_post( $value );

        return $this;
    }

    /**
     * Update Field Values
     *
     * @param $data
     * @return $this
     */
    public function update_field_values( $data )
    {
        foreach( $data as $field_ref => $value )
        {
            $this->update_field_value( $field_ref, $value );
        }

        return $this;
    }

    public function get_extra_value( $key )
    {
        if( ! isset( $this->_extra_values[ $key ] ) ||  ! $this->_extra_values[ $key ] ){
            $id = ( $this->_id ) ? $this->_id : 0;
            $this->_extra_values[ $key ] = get_post_meta( $id, $key, TRUE );
        }

        return $this->_extra_values[ $key ];
    }

    public function get_extra_values( $keys )
    {
        $values = array();

        foreach( $keys as $key ) {
            $values[ $key ] = $this->get_extra_value( $key );
        }

        return $values;
    }

    public function update_extra_value( $key, $value )
    {
        if( property_exists( $this, $key ) ) return FALSE;

        return $this->_extra_values[ $key ] = $value;
    }

    public function update_extra_values( $values )
    {
        foreach( $values as $key => $value ){
            $this->update_extra_value( $key, $value );
        }
    }

    /**
     * Find Submissions
     *
     * @param $form_id
     * @param array $where
     * @return array
     */
    public function find( $form_id, array $where = array() )
    {
        $this->_form_id = $form_id;

        $args = array(
            'post_type' => 'nf_sub',
            'posts_per_page' => -1,
            'meta_query' => $this->format_meta_query( $where )
        );

        $subs = get_posts( $args );

        $class = get_class( $this );

        $return = array();
        foreach( $subs as $sub ){
            $return[] = new $class( $sub->ID, $this->_form_id );
        }

        return $return;
    }

    /**
     * Delete Submission
     */
    public function delete()
    {
        if( ! $this->_id ) return;

        wp_delete_post( $this->_id );
    }

    /**
     * Save Submission
     *
     * @return $this|NF_Database_Models_Submission|void
     */
    public function save()
    {
        if( ! $this->_id ){

            $sub = array(
                'post_type' => 'nf_sub',
                'post_status' => 'publish'
            );

            $this->_id = wp_insert_post( $sub );

            // Log Error
            if( ! $this->_id ) return;
        }

        if( ! $this->_seq_num && $this->_form_id ){

            $this->_seq_num = NF_Database_Models_Form::get_next_sub_seq( $this->_form_id );
        }

        $this->_save_extra_values();

        return $this->_save_field_values();
    }

    public static function export( $form_id, array $sub_ids = array(), $return = FALSE )
    {
        $date_format = Ninja_Forms()->get_setting( 'date_format' );


        /*
         * Labels
         */

        $field_labels = array(
            '_seq_num' => '#',
            '_date_submitted' => __( 'Date Submitted', 'ninja-forms' )
        );

        // Legacy Filter from 2.9.*
        $field_labels = apply_filters( 'nf_subs_csv_label_array_before_fields', $field_labels, $sub_ids );

        $fields = Ninja_Forms()->form( $form_id )->get_fields();

        usort( $fields, array( self, sort_fields ) );

        $hidden_field_types = apply_filters( 'nf_sub_hidden_field_types', array() );

        foreach( $fields as $field ){

            if( in_array( $field->get_setting( 'type' ), $hidden_field_types ) ) continue;

            $field_labels[ $field->get_id() ] = $field->get_setting( 'label' );
        }


        /*
         * Submissions
         */

        $value_array = array();

        $subs = Ninja_Forms()->form( $form_id )->get_subs();

        foreach( $subs as $sub ){

            if( ! in_array( $sub->get_id(), $sub_ids ) ) continue;

            $value[ '_seq_num' ] = $sub->get_seq_num();
            $value[ '_date_submitted' ] = $sub->get_sub_date( $date_format );

            foreach( $field_labels as $field_id => $label ){

                if( ! is_int( $field_id ) ) continue;

                $field_value = $sub->get_field_value( $field_id );
                $field_value = apply_filters( 'nf_subs_export_pre_value', $field_value, $field_id );
                $field_value = apply_filters( 'ninja_forms_subs_export_pre_value', $field_value, $field_id, $form_id );

                if( is_array( $field_value ) ){
                    $field_value = implode( ' | ', $field_value );
                }

                $value[ $field_id ] = $field_value;
            }

            $value_array[] = $value;
        }

        $value_array = WPN_Helper::stripslashes( $value_array );

        // Legacy Filter from 2.9.*
        $value_array = apply_filters( 'nf_subs_csv_value_array', $value_array, $sub_ids );

        $csv_array[ 0 ][] = $field_labels;
        $csv_array[ 1 ][] = $value_array;

        $today = date( $date_format, current_time( 'timestamp' ) );
        $filename = apply_filters( 'nf_subs_csv_filename', 'nf_subs_' . $today );
        $filename = $filename . ".csv";

        if( $return ){
            return WPN_Helper::str_putcsv( $csv_array,
                apply_filters( 'nf_sub_csv_delimiter', ',' ),
                apply_filters( 'nf_sub_csv_enclosure', '"' ),
                apply_filters( 'nf_sub_csv_terminator', "\n" )
            );
        }else{
            header( 'Content-type: application/csv');
            header( 'Content-Disposition: attachment; filename="'.$filename .'"' );
            header( 'Pragma: no-cache');
            header( 'Expires: 0' );
            echo apply_filters( 'nf_sub_csv_bom',"\xEF\xBB\xBF" ) ; // Byte Order Mark
            echo WPN_Helper::str_putcsv( $csv_array,
                apply_filters( 'nf_sub_csv_delimiter', ',' ),
                apply_filters( 'nf_sub_csv_enclosure', '"' ),
                apply_filters( 'nf_sub_csv_terminator', "\n" )
            );

            die();
        }
    }

    /*
     * PROTECTED METHODS
     */

    /**
     * Save Field Value
     *
     * @param $field_id
     * @param $value
     * @return $this
     */
    protected function _save_field_value( $field_id, $value )
    {
        update_post_meta( $this->_id, '_field_' . $field_id, $value );

        return $this;
    }

    /**
     * Save Field Values
     *
     * @return $this|void
     */
    protected function _save_field_values()
    {
        if( ! $this->_field_values ) return FALSE;

        foreach( $this->_field_values as $field_id => $value )
        {
            $this->_save_field_value( $field_id, $value );
        }

        update_post_meta( $this->_id, '_form_id', $this->_form_id );

        update_post_meta( $this->_id, '_seq_num', $this->_seq_num );

        return $this;
    }

    protected function _save_extra_values()
    {
        if( ! $this->_extra_values ) return FALSE;

        foreach( $this->_extra_values as $key => $value )
        {
            if( property_exists( $this, $key ) ) continue;

            update_post_meta( $this->_id, $key, $value );
        }
    }


    /*
     * UTILITIES
     */

    /**
     * Format Meta Query
     *
     * @param array $where
     * @return array
     */
    protected function format_meta_query( array $where = array() )
    {
        $return = array(
            array(
                'key' => '_form_id',
                'value' => $this->_form_id
            )
        );

        if( ! empty( $where ) ) {
            foreach ($where as $ref => $value) {

                $field_id = ( is_int( $ref ) ) ? $ref : $this->get_field_id_by_key( $ref );

                $return[] = ( is_array($value) ) ? $value : array('key' => "_field_$field_id", 'value' => $value);
            }
        }

        return $return;
    }

    /**
     * Get Field ID By Key
     *
     * @param $field_key
     * @return mixed
     */
    protected function get_field_id_by_key( $field_key )
    {
        global $wpdb;

        $field_id = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}nf3_fields WHERE `key` = '{$field_key}' AND `parent_id` = {$this->_form_id}" );

        return $field_id;
    }

    public static function sort_fields( $a, $b )
    {
        if ( $a->get_setting( 'order' ) == $b->get_setting( 'order' ) ) {
            return 0;
        }
        return ( $a->get_setting( 'order' ) < $b->get_setting( 'order' ) ) ? -1 : 1;
    }


} // End NF_Database_Models_Submission
