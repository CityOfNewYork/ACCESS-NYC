<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_AJAX_Processes_UpdateFields extends WP_Background_Process
{
    protected $action = 'nf_update_fields';

    protected function task( $item )
    {
        if( ! isset( $item[ 'id' ]       ) ) return false;
        if( ! isset( $item[ 'settings' ] ) ) return false;

        $field = Ninja_Forms()->form()->get_field( $item[ 'id' ] );

        $field->update_settings( $item[ 'settings' ] )->save();

        return false;
    }
}