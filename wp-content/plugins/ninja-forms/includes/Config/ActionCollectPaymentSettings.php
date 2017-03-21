<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_action_collect_payment_settings', array(

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    */

    'payment_gateways' => array(
        'name' => 'payment_gateways',
        'type' => 'select',
        'label' => __( 'Payment Gateways', 'ninja-forms' ),
        'options' => array(
            array(
                'label' => '--',
                'value' => ''
            ),
        ),
        'value' => '',
        'width' => 'full',
        'group' => 'primary',
    ),

    /*
    |--------------------------------------------------------------------------
    | Payment Total
    |--------------------------------------------------------------------------
    */

    'payment_total' => array(
        'name' => 'payment_total',
        'type' => 'textbox',
        'label' => __( 'Payment Total', 'ninja-forms' ),
        'width' => 'full',
        'group' => 'primary',
        'use_merge_tags' => array(
            'include' => array( 'calcs' ),
            'exclude' => array(
                'user',
                'post',
                'system'
            )
        )
    ),

));