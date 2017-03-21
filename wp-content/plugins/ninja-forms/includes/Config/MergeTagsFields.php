<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_merge_tags_fields', array(

    /*
    |--------------------------------------------------------------------------
    | All Fields
    |--------------------------------------------------------------------------
    */

    'all_fields' => array(
        'id' => 'all_fields',
        'tag' => '{field:all_fields}',
        'label' => __( 'All Fields', 'ninja_forms' ),
        'callback' => 'all_fields',
        'fields' => array()
    ),

));