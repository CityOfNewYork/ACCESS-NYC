<?php if ( ! defined( 'ABSPATH' ) ) exit;

return array(

    /*
    * Redirect URL
    */

    'redirect_url' => array(
        'name' => 'redirect_url',
        'type' => 'textbox',
        'group' => 'primary',
        'label' => __( 'URL', 'ninja-forms' ),
        'placeholder' => '',
        'width' => 'full',
        'value' => '',
        'use_merge_tags' => TRUE,
    ),

);
