<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_merge_tags_system', array(

    /*
    |--------------------------------------------------------------------------
    | Admin Email Address
    |--------------------------------------------------------------------------
    */

    'admin_email' => array(
        'id' => 'admin_email',
        'tag' => '{system:admin_email}',
        'label' => __( 'Admin Email', 'ninja_forms' ),
        'callback' => 'admin_email'
    ),

    /*
    |--------------------------------------------------------------------------
    | System Date
    |--------------------------------------------------------------------------
    */

    'date' => array(
        'id' => 'date',
        'tag' => '{system:date}',
        'label' => __( 'Date', 'ninja_forms' ),
        'callback' => 'system_date'
    ),

    /*
    |--------------------------------------------------------------------------
    | System IP Address
    |--------------------------------------------------------------------------
    */

    'ip' => array(
        'id' => 'ip',
        'tag' => '{system:ip}',
        'label' => __( 'IP Address', 'ninja_forms' ),
        'callback' => 'system_ip'
    ),

    /*
    |--------------------------------------------------------------------------
    | Site Title
    |--------------------------------------------------------------------------
    */

    'site_title' => array(
        'id' => 'site_title',
        'tag' => '{site:title}',
        'label' => __( 'Site Title', 'ninja_forms' ),
        'callback' => 'site_title'
    ),

    /*
    |--------------------------------------------------------------------------
    | Site URL
    |--------------------------------------------------------------------------
    */

    'site_url' => array(
        'id' => 'site_url',
        'tag' => '{site:url}',
        'label' => __( 'Site URL', 'ninja_forms' ),
        'callback' => 'site_url'
    ),

));