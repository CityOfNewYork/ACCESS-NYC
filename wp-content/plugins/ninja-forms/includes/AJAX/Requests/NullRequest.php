<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_AJAX_Requests_NullRequest extends WP_Async_Request
{
    protected $action = 'nf_null_request';

    protected function handle() {
        // This section intentionally left blank.
    }
}
