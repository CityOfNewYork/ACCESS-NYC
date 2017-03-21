<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_from_action_defaults', array(

    array(
        'id'      => 'tmp-1',
        'label'   => __( 'Success Message', 'ninja-forms' ),
        'type'    => 'successmessage',
        'message' => __( 'Your form has been successfully submitted.', 'ninja-forms' ),
        'order'   => 1,
        'active'  => TRUE,
    ),

    array(
        'id'      => 'tmp-2',
        'label'   => __( 'Admin Email', 'ninja-forms' ),
        'type'    => 'email',
        'to'      => array( get_option( 'admin_email' ) ),
        'subject' => __( 'Ninja Forms Submission', 'ninja-forms' ),
        'message' => '{field:all_fields}',
        'order'   => 2,
        'active'  => TRUE,
    ),

    array(
        'id'    => 'tmp-3',
        'label' => __( 'Save Submission', 'ninja-forms' ),
        'type'  => 'save',
        'order' => 3,
        'active'=> TRUE,
    ),

));