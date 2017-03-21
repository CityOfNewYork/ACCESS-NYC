<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_action_settings', array(

    /*
     * Label
     */

    'label' => array(
        'name' => 'label',
        'type' => 'textbox',
        'group' => 'primary',
        'label' => __( 'Action Name', 'ninja-forms' ),
        'placeholder' => '',
        'width' => 'full',
        'value' => '',
        
    ),    

    /*
     * Active
     */

    'active' => array(
        'name' => 'active',
        'type' => 'toggle',
        'label' => __( 'Active', 'ninja-forms' ),
        'value' => 1
    ),

) );
