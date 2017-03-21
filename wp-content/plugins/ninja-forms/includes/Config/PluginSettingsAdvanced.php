<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_plugin_settings_advanced', array(

    /*
    |--------------------------------------------------------------------------
    | Delete on Uninstall
    |--------------------------------------------------------------------------
    */

    'delete_on_uninstall' => array(
        'id'    => 'delete_on_uninstall',
        'type'  => 'checkbox',
        'label' => __( 'Remove ALL Ninja Forms data upon uninstall?', 'ninja-forms' ),
        'desc'  => sprintf( __( 'If this box is checked, ALL Ninja Forms data will be removed from the database upon deletion. %sAll form and submission data will be unrecoverable.%s', 'ninja-forms' ), '<span class="nf-nuke-warning">', '</span>' ),
    ),

    /*
    |--------------------------------------------------------------------------
    | Delete Prompt for Delete on Uninstall
    |--------------------------------------------------------------------------
    */

    'delete_prompt' => array(
        'id'    => 'delete_prompt',
        'type'  => 'prompt',
        'desc'  => __( 'This setting will COMPLETELY remove anything Ninja Forms related upon plugin deletion. This includes SUBMISSIONS and FORMS. It cannot be undone.', 'ninja-forms' ),
        'deps'  => array(
            'delete_on_uninstall' => 'checked'
        )
    ),

    /*
    |--------------------------------------------------------------------------
    | Disable Admin Notices
    |--------------------------------------------------------------------------
    */

    'disable_admin_notices' => array(
        'id'    => 'disable_admin_notices',
        'type'  => 'checkbox',
        'label' => __( 'Disable Admin Notices', 'ninja-forms' ),
        'desc'  => __( 'Never see an admin notice on the dashboard from Ninja Forms. Uncheck to see them again.', 'ninja-forms' ),
    ),

    /*
    |--------------------------------------------------------------------------
    | Opinionated Styles
    |--------------------------------------------------------------------------
    */

    'opinionated_styles' => array(
        'id'    => 'opinionated_styles',
        'type'  => 'select',
        'label' => __( 'Opinionated Styles', 'ninja-forms' ),
        'options' => array(
            array(
                'label' => __( 'None', 'ninja-forms' ),
                'value' => '',
            ),
            array(
                'label' => __( 'Light', 'ninja-forms' ),
                'value' => 'light',
            ),
            array(
                'label' => __( 'Dark', 'ninja-forms' ),
                'value' => 'dark',
            ),
        ),
        'desc'  => __( 'Use default Ninja Forms styling conventions.', 'ninja-forms' ),
        'value' => ''
    ),

    /*
    |--------------------------------------------------------------------------
    | Rollback to v2.9.x
    |--------------------------------------------------------------------------
    */

    'rollback' => array(
        'id'    => 'rollback',
        'type'  => 'html',
        'html' => '<a href="' . admin_url( 'admin.php?page=ninja-forms&nf-switcher=rollback' ) . '" class="button">' . __( 'Rollback', 'ninja-forms' ) . '</a>',
        'label' => __( 'Rollback to v2.9.x', 'ninja-forms' ),
        'desc'  => __( 'Rollback to the most recent 2.9.x release.', 'ninja-forms' ) . '<br /><div style="color: red">' . __( 'IMPORTANT: All 3.0 data will be removed.', 'ninja-forms' ) . '<br />' . __( 'Please export any forms or submissions you do not want to be lost during this process.', 'ninja-forms' ) . '</div>',
    ),

));
