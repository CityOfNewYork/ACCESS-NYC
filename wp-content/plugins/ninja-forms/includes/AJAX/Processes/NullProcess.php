<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_AJAX_Processes_NullProcess extends WP_Background_Process
{
    protected $action = 'nf_null_process';

    protected function task( $item )
    {
        // This section intentionally left blank.
        return false;
    }
}