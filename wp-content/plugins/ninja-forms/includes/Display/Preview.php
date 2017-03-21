<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Display_Preview
 */
final class NF_Display_Preview
{
    protected $form_id = '';

    public function __construct()
    {
        if ( ! isset( $_GET['nf_preview_form'] ) ) return;

        $this->_form_id = $_GET['nf_preview_form'];

        add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

        add_filter('the_title', array( $this, 'the_title' ) );
        remove_filter( 'the_content', 'wpautop' );
        remove_filter( 'the_excerpt', 'wpautop' );
        add_filter('the_content', array( $this, 'the_content' ), 9001 );
        add_filter('get_the_excerpt', array( $this, 'the_content' ) );
        add_filter('template_include', array( $this, 'template_include' ) );
        add_filter('post_thumbnail_html', array( $this, 'post_thumbnail_html' ) );
    }

    public function pre_get_posts( $query )
    {
        $query->set( 'posts_per_page', 1 );
    }

    /**
     * @return string
     */
    function the_title( $title )
    {
        if( ! in_the_loop() ) return $title;

        $form_title = Ninja_Forms()->form( $this->_form_id )->get()->get_setting( 'title' );

        return $form_title . " " . __( 'Preview', 'ninja-forms' );
    }

    /**
     * @return string
     */
    function the_content()
    {
        if ( ! is_user_logged_in() ) return __( 'You must be logged in to preview a form.', 'ninja-forms' );

        return do_shortcode( "[nf_preview id='{$this->_form_id}']" );
    }

    /**
     * @return string
     */
    function template_include()
    {
        return locate_template( array( 'page.php', 'single.php', 'index.php' ) );
    }

    function post_thumbnail_html() {
    	return '';
    }

} // END CLASS NF_Display_Preview
