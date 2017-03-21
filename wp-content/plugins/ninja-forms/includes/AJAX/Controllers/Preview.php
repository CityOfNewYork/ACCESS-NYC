<?php if ( ! defined( 'ABSPATH' ) ) exit;

class NF_AJAX_Controllers_Preview extends NF_Abstracts_Controller
{
    private static $transient_prefix = 'nf_form_preview_';

    public function __construct()
    {
        add_action( 'wp_ajax_nf_preview_update', array( $this, 'update' ) );

        add_filter( 'ninja_forms_run_action_settings', array( $this, 'filter_action_settings' ), 10, 4 );
    }

    public function update()
    {
        check_ajax_referer( 'ninja_forms_builder_nonce', 'security' );

        $form = json_decode( stripslashes( $_POST['form'] ), ARRAY_A );

        $form_id = $form[ 'id' ];

        $form_data = $this->get_form_data( $form_id );

        /*
         * Form Settings
         */

        if( isset( $form[ 'settings' ] ) && is_array( $form[ 'settings' ] ) ) {

            $old_settings = $form_data[ 'settings' ];

            $form_data[ 'settings' ] = array_merge( $old_settings, $form[ 'settings' ] );
        }

        /*
         * Fields and Field Settings
         */

        if( isset( $form[ 'fields' ] ) && is_array( $form[ 'fields' ] ) ) {

            foreach( $form[ 'fields' ] as $field ){

                $id = $field[ 'id' ];

                $old_settings = ( isset( $form_data[ 'fields' ][ $id ][ 'settings' ] ) ) ? $form_data[ 'fields' ][ $id ][ 'settings' ] : array();

                $new_settings = array_merge( $old_settings, $field[ 'settings' ] );

                $form_data[ 'fields' ][ $id ][ 'settings' ] = $new_settings;
            }
        }

        if( isset( $form[ 'deleted_fields' ] ) ) {

            foreach( $form[ 'deleted_fields' ] as $deleted_field ){

                unset( $form_data[ 'fields' ][ $deleted_field ] );
            }
        }

        /*
         * Actions and Action Settings
         */

        if( isset( $form[ 'actions' ] ) && is_array( $form[ 'actions' ] ) ) {

            foreach( $form[ 'actions' ] as $action ){

                $id = $action[ 'id' ];

                if( isset( $form[ 'deleted_actions' ][ $id ] ) ) {

                    unset( $form_data[ 'actions' ][ $id ] );
                    continue;
                }

                $old_settings = ( isset ( $form_data[ 'actions' ][ $id ][ 'settings' ] ) ) ? $form_data[ 'actions' ][ $id ][ 'settings' ]: array();

                $new_settings = array_merge( $old_settings, $action[ 'settings' ] );

                $form_data[ 'actions' ][ $id ][ 'settings' ] = $new_settings;
            }
        }

        if( isset( $form[ 'deleted_actions' ] ) ) {

            foreach( $form[ 'deleted_actions' ] as $deleted_action ){

                unset( $form_data[ 'actions' ][ $deleted_action ] );
            }
        }



        $this->update_form_data( $form_data );

        $this->_data['form'] = $form_data;

        do_action( 'ninja_forms_save_form_preview', $form_id );

        $this->_respond();
    }

    public function filter_action_settings( $action_settings, $form_id, $action_id, $form_settings )
    {
        if( ! isset( $form_settings[ 'is_preview' ] ) ) return $action_settings;

        $form_data = $this->get_form_data( $form_id );

        if( isset( $form_data[ 'actions' ][ $action_id ] ) ){

            $settings = $form_data['actions'][$action_id]['settings'];
            $action_settings = array_merge( $action_settings, $settings );
        }

        return $action_settings;
    }

    private function get_form_data( $form_id )
    {
        $form_data = get_user_option( self::$transient_prefix . $form_id, FALSE );

        if( ! $form_data ){

            if( is_string( $form_id ) ){
                $form = Ninja_Forms()->form()->get();
                $form_data['id'] = $form_id;
                $form_data[ 'settings' ] = array();
                $form_data[ 'fields' ] = array();
                $form_data[ 'actions' ] = array();
            } else {
                $form = Ninja_Forms()->form($form_id)->get();
                $form_data['id'] = $form_id;

                $form_data[ 'settings' ] = $form->get_settings();

                $fields = Ninja_Forms()->form( $form_id )->get_fields();
                foreach( $fields as $field ){

                    $field_id = $field->get_id();
                    $form_data[ 'fields' ][ $field_id ][ 'settings' ] = $field->get_settings();
                }

                $actions = Ninja_Forms()->form( $form_id )->get_actions();
                foreach( $actions as $action ){

                    $action_id = $action->get_id();
                    $form_data[ 'actions' ][ $action_id ][ 'settings' ] = $action->get_settings();
                }
            }
        }

        return $form_data;
    }

    private function update_form_data( $form_data )
    {
        $update = update_user_option( get_current_user_id(), self::$transient_prefix . $form_data['id'], $form_data );

        $this->_data[ 'updated' ] = $update;

        if( ! $update ){
            $this->_errors[ 'Form Preview Not Updated' ] = $form_data;
            $this->_errors[ 'Current User' ] = get_current_user_id();
            $this->_errors[ 'Option' ] = self::$transient_prefix . $form_data['id'];
        }
    }
}
