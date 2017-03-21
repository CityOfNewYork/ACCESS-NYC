<?php if ( ! defined( 'ABSPATH' ) ) exit;

class NF_AJAX_Controllers_SavedFields extends NF_Abstracts_Controller
{
    public function __construct()
    {
        add_action( 'wp_ajax_nf_create_saved_field', array( $this, 'create' ) );
        add_action( 'wp_ajax_nf_update_saved_field', array( $this, 'update' ) );
        add_action( 'wp_ajax_nf_delete_saved_field', array( $this, 'delete' ) );
    }

    public function create()
    {
        check_ajax_referer( 'ninja_forms_builder_nonce', 'security' );

        if( ! isset( $_POST[ 'field' ] ) ){
            $this->_errors[] = __( 'Field Not Found', 'ninja-forms' );
            $this->_respond();
        }

        $field_settings = json_decode( stripslashes( $_POST[ 'field' ] ), ARRAY_A );

        $field = Ninja_Forms()->form()->field()->get();
        $field->update_settings( $field_settings );
        $field->update_setting( 'saved', 1 );
        $field->save();

        $this->_data[ 'id' ] = $field->get_id();

        $this->_respond();
    }

    public function update()
    {
        check_ajax_referer( 'ninja_forms_builder_nonce', 'security' );

        if( ! isset( $_POST[ 'field' ] ) ){
            $this->_errors[] = __( 'Field Not Found', 'ninja-forms' );
            $this->_respond();
        }

        $this->_respond();
    }

    public function delete()
    {
        check_ajax_referer( 'ninja_forms_settings_nonce', 'security' );

        if( ! isset( $_POST[ 'field' ] ) ){
            $this->_errors[] = __( 'Field Not Found', 'ninja-forms' );
            $this->_respond();
        }

        $id = absint( $_POST[ 'field' ][ 'id' ] );

        $errors = Ninja_Forms()->form()->get_field( $id )->delete();

        $this->_data[ 'id' ] = $id;
        $this->_data[ 'errors' ] = $errors;

        $this->_respond();
    }


}
