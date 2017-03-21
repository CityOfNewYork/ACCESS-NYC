<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_merge_tags_user', array(

    /*
    |--------------------------------------------------------------------------
    | User ID
    |--------------------------------------------------------------------------
    */

    'user_id' => array(
        'id' => 'user_id',
        'tag' => '{user:id}',
        'label' => __( 'User ID', 'ninja_forms' ),
        'callback' => 'user_id'
    ),

    /*
    |--------------------------------------------------------------------------
    | User First Name
    |--------------------------------------------------------------------------
    */

    'first_name' => array(
        'id' => 'first_name',
        'tag' => '{user:first_name}',
        'label' => __( 'First Name', 'ninja_forms' ),
        'callback' => 'user_first_name'
    ),

    /*
    |--------------------------------------------------------------------------
    | User Last Name
    |--------------------------------------------------------------------------
    */

    'last_name' => array(
        'id' => 'last_name',
        'tag' => '{user:last_name}',
        'label' => __( 'Last Name', 'ninja_forms' ),
        'callback' => 'user_last_name'
    ),

    /*
    |--------------------------------------------------------------------------
    | User Dispaly Name
    |--------------------------------------------------------------------------
    */

    'display_name' => array(
        'id' => 'display_name',
        'tag' => '{user:display_name}',
        'label' => __( 'Display Name', 'ninja_forms' ),
        'callback' => 'user_display_name'
    ),

    /*
    |--------------------------------------------------------------------------
    | User Email Address
    |--------------------------------------------------------------------------
    */

    'user_email' => array(
        'id' => 'user_email',
        'tag' => '{user:email}',
        'label' => __( 'Email', 'ninja_forms' ),
        'callback' => 'user_email'
    ),

));