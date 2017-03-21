<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_Admin_Metaboxes_AppendAForm extends NF_Abstracts_Metabox
{
    protected $_post_types = array( 'post', 'page' );

    public function __construct()
    {
        parent::__construct();

        $this->_title = __( 'Append a Ninja Form', 'ninja-forms' );

        add_filter( 'the_content', array( $this, 'append_form' ) );
    }

    public function append_form( $content )
    {
        $post = $GLOBALS['post'];
        $form_id = get_post_meta( $post->ID, 'ninja_forms_form', TRUE );

        if( ! $form_id ) return $content;

        return $content . "[ninja_forms id=$form_id]";
    }

    public function save_post( $post_id )
    {
        if (
            defined('DOING_AUTOSAVE') && DOING_AUTOSAVE
            || ! isset( $_POST['nf_append_form'] )
            || ! wp_verify_nonce( $_POST['nf_append_form'], 'ninja_forms_append_form' )
            || ( 'page' == $_POST['post_type'] && !current_user_can( 'edit_page', $post_id ) )
            || ( 'post' == $_POST['post_type'] && !current_user_can( 'edit_post', $post_id ) )
        ) return $post_id;

        $post_id = absint( $post_id );

        $form_id = absint( $_POST['ninja_form_select'] );

        if ( empty ( $form_id ) ) {
            delete_post_meta( $post_id, 'ninja_forms_form' );
        } else {
            update_post_meta( $post_id, 'ninja_forms_form', $form_id );
        }
    }

    public function render_metabox( $post, $metabox )
    {
        wp_nonce_field( 'ninja_forms_append_form', 'nf_append_form' );

        $forms = Ninja_Forms()->form()->get_forms();

        $form_id = get_post_meta( $post->ID, 'ninja_forms_form', true );

        $none_text = '-- ' . __( 'None', 'ninja-forms' );

        Ninja_Forms()->template( 'admin-metabox-append-a-form.html.php', compact( 'forms', 'form_id', 'none_text' ) );
    }
}