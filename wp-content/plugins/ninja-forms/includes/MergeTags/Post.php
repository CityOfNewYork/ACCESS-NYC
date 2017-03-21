<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_MergeTags_Post
 */
final class NF_MergeTags_Post extends NF_Abstracts_MergeTags
{
    protected $id = 'post';

    public function __construct()
    {
        parent::__construct();
        $this->title = __( 'Post', 'ninja-forms' );
        $this->merge_tags = Ninja_Forms()->config( 'MergeTagsPost' );
    }

    protected function post_id()
    {
        global $post;
        return ( is_object ( $post ) ) ? $post->ID : '';
    }

    protected function post_title()
    {
        global $post;
        return ( is_object ( $post ) ) ? $post->post_title : '';
    }

    protected function post_url()
    {
        global $post;
        return ( is_object ( $post ) ) ? get_permalink( $post->ID ) : '';
    }

    protected function post_author()
    {
        global $post;
        if( ! is_object( $post )  ) return;
        $author = get_user_by('id', $post->post_author);
        return $author->display_name;
    }

    protected function post_author_email()
    {
        global $post;
        if( ! is_object( $post ) ) return;
        $author = get_user_by( 'id', $post->post_author );
        return $author->user_email;
    }

} // END CLASS NF_MergeTags_System
