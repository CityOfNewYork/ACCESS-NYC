<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_AJAX_Requests_DeleteField extends WP_Async_Request
{
    protected $action = 'nf_delete_field';

    protected function handle() {

        if( ! isset( $_POST[ 'field_id' ] ) ) return;

        $field = Ninja_Forms()->form()->get_field( $_POST[ 'field_id' ] );
        $field->delete();
    }
}
