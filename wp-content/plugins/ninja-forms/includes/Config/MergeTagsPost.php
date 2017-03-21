<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_merge_tags_post', array(

    /*
    |--------------------------------------------------------------------------
    | Post ID
    |--------------------------------------------------------------------------
    */

    'id' => array(
        'id' => 'id',
        'tag' => '{post:id}',
        'label' => __( 'Post ID', 'ninja_forms' ),
        'callback' => 'post_id'
    ),

    /*
    |--------------------------------------------------------------------------
    | Post Title
    |--------------------------------------------------------------------------
    */

    'title' => array(
        'id' => 'title',
        'tag' => '{post:title}',
        'label' => __( 'Post Title', 'ninja_forms' ),
        'callback' => 'post_title'
    ),

    /*
    |--------------------------------------------------------------------------
    | Post URL
    |--------------------------------------------------------------------------
    */

    'url' => array(
        'id' => 'url',
        'tag' => '{post:url}',
        'label' => __( 'Post URL', 'ninja_forms' ),
        'callback' => 'post_url'
    ),

    /*
    |--------------------------------------------------------------------------
    | Post Author
    |--------------------------------------------------------------------------
    */

    'author' => array(
        'id' => 'author',
        'tag' => '{post:author}',
        'label' => __( 'Post Author', 'ninja_forms' ),
        'callback' => 'post_author'
    ),

    /*
    |--------------------------------------------------------------------------
    | Post Author Email
    |--------------------------------------------------------------------------
    */

    'author_email' => array(
        'id' => 'author_email',
        'tag' => '{post:author_email}',
        'label' => __( 'Post Author Email', 'ninja_forms' ),
        'callback' => 'post_author_email'
    ),

));