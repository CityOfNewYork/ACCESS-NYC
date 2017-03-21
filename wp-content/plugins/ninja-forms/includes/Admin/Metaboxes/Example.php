<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_Admin_Metaboxes_Example extends NF_Abstracts_SubmissionMetabox
{
    public function __construct()
    {
        parent::__construct();

//        $this->_title = __( 'Example Metabox', 'ninja-forms' );
    }

    public function render_metabox( $post, $metabox )
    {
        $data = $this->sub->get_field_values();

        Ninja_Forms()->template( 'admin-metabox-submission-example.php', compact( 'data' ) );
    }
}