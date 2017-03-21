<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_from_calculation_settings', array(

    /*
    * Calculation
    */

    'calculations' => array(
        'name'              => 'calculations',
        'type'              => 'option-repeater',
        'label'             => ' <a href="#" class="nf-add-new">' . __( 'Add New', 'ninja-forms' ) . '</a>',
        'width'             => 'full',
        'group'             => 'primary',
        'tmpl_row'          => 'tmpl-nf-edit-setting-calculation-repeater-row',
        'columns'           => array(
            'name'          => array(
                'header'    => __( 'Variable Name', 'ninja-forms' ),
                'default'   => '',
            ),
            'eq'            => array(
                'header'    => __( 'Equation', 'ninja-forms' ),
                'default'   => '',
            ),
        ),
        'use_merge_tags'    => array(
            'exclude'       => array(
                'user', 'system', 'post'
            ),
        ),
    ),


));