<?php

/*
|--------------------------------------------------------------------------
| Deprecated Functions
|--------------------------------------------------------------------------
*/

function ninja_forms_display_form( $form_id = '' ){
    Ninja_Forms::deprecated_notice( 'ninja_forms_display_form', '3.0', 'Ninja_Forms()->display( $form_id, $is_preview )', debug_backtrace() );
    Ninja_Forms()->display( $form_id );
}

function ninja_forms_get_fields_by_form_id($form_id, $orderby = 'ORDER BY `order` ASC'){

    $fields = Ninja_Forms()->form( $form_id )->get_fields();

    $field_results = array();
    foreach( $fields as $field ){
        $field_results[] = array(
            'id'      => $field->get_id(),
            'form_id' => $form_id,
            'type'    => $field->get_setting( 'type' ),
            'order'   => $field->get_setting( 'order' ),
            'data'    => $field->get_settings(),
            'fav_id'  => null,
            'def_id'  => null,
        );
    }
    return $field_results;
}

/**
 * Included for backwards compatibility with Visual Composer.
 */
function ninja_forms_get_all_forms(){
//    Ninja_Forms::deprecated_notice( 'ninja_forms_get_all_forms', '3.0', 'Ninja_Forms()->form()->get_forms()', debug_backtrace() );
    $forms = array();
    foreach( Ninja_Forms()->form()->get_forms() as $form ){
        $forms[] = array(
            'id' => $form->get_id(),
            'data' => $form->get_settings(),
            'name' => $form->get_setting( 'title' )
        );
    }
    return $forms;
}

function nf_is_func_disabled( $function ){
    Ninja_Forms::deprecated_notice( 'nf_is_func_disabled', '3.0', 'WPN_Helper::is_func_disabled()', debug_backtrace() );
    return WPN_Helper::is_func_disabled( $function );
}

/*
|--------------------------------------------------------------------------
| Deprecated Hooks
|--------------------------------------------------------------------------
*/

add_action( 'init', '_nf_get_actions', 999 );
function _nf_get_actions() {
    if ( isset( $_POST['nf_action'] ) ) {
        do_action( 'nf_' . $_POST['nf_action'], $_POST );
    }
}

add_action( 'init', '_nf_post_actions', 999 );
function _nf_post_actions() {
    if ( isset( $_GET['nf_action'] ) ) {
        do_action( 'nf_' . $_GET['nf_action'], $_GET );
    }
}

// add_action( 'shutdown', '_nf_removed_hooks' );
function _nf_removed_hooks() {
    global $wp_filter;

    $hooks = array(
        /* Removed Action Hooks */
        'ninja_forms_insert_sub',
        'nf_email_notification_after_settings',
        'nf_edit_notification_settings',
        'ninja_forms_edit_field_before_li',
        'ninja_forms_edit_field_after_li',
        'ninja_forms_edit_field_before_closing_li',
        'ninja_forms_edit_field_before_registered',
//    'nf_edit_field_*',
        'ninja_forms_edit_field_after_registered',
        'ninja_forms_edit_field_before_ul',
        'ninja_forms_edit_field_ul',
        'ninja_forms_edit_field_after_ul',
        'ninja_forms_email_admin',
        'ninja_forms_email_user',
        'ninja_forms_display_before_field_label',
        'ninja_forms_display_field_label',
        'ninja_forms_display_after_field_label',
        'ninja_forms_display_field_help',
        'ninja_forms_display_field_label',
        'ninja_forms_display_field_help',
        'nf_before_display_loading',
        'ninja_forms_display_open_form_wrap',
        'ninja_forms_display_form_title',
        'ninja_forms_display_open_form_tag',
        'ninja_forms_display_fields',
        'ninja_forms_display_close_form_tag',
        'ninja_forms_display_close_form_wrap',
        'nf_notification_before_process',
        'nf_save_notification',
        'nf_sub_table_after_row_actions_trash',
        'nf_sub_table_after_row_actions',
        'nf_sub_table_before_row_actions_trash',
        'nf_sub_table_before_row_actions',
        'ninja_forms_after_import_form',
        'ninja_forms_display_after_closing_field_wrap',
        'ninja_forms_display_after_field_function',
        'ninja_forms_display_after_field_label',
        'ninja_forms_display_after_field',
        'ninja_forms_display_after_opening_field_wrap',
        'ninja_forms_display_before_closing_field_wrap',
        'ninja_forms_display_before_field_function',
        'ninja_forms_display_before_field_label',
        'ninja_forms_display_before_field',
        'ninja_forms_display_before_opening_field_wrap',
        'ninja_forms_display_css',
        'ninja_forms_save_admin_metabox_option',
        'ninja_forms_save_admin_metabox',
        'ninja_forms_save_admin_sidebar',
        'ninja_forms_save_admin_tab',
        'ninja_forms_before_pre_process',
        'ninja_forms_display_after_fields',
        'ninja_forms_display_after_form_title',
        'ninja_forms_display_after_form_wrap',
        'ninja_forms_display_after_open_form_tag',
        'ninja_forms_display_before_fields',
        'ninja_forms_display_before_form_title',
        'ninja_forms_display_before_form_wrap',
        'ninja_forms_display_before_form',
        'ninja_forms_post_process',
        'ninja_forms_pre_process',
        'ninja_forms_process',

        /* Removed Filter Hooks */
        'nf_export_form_row',
        'nf_notification_admin_js_vars',
        'nf_success_message_locations',
        'nf_notification_types',
        'ninja_forms_admin_submissions_datepicker_args',
        'ninja_forms_starter_form_contents',
        'ninja_forms_preview_page_title',
        'nf_input_limit_types',
        'ninja_forms_edit_field_li_label',
        'nf_edit_field_settings_sections',
        'ninja_forms_use_post_fields',
        'nf_general_settings_advanced',
        'nf_new_form_defaults',
        'ninja_forms_use_post_fields',
        'ninja_forms_form_settings_basic',
        'ninja_forms_form_settings_restrictions',
        'nf_upgrade_handler_register',
        'ninja_forms_save_sub',
        'ninja_forms_export_subs_csv_file_name',
        'ninja_forms_export_sub_label',
        'ninja_forms_export_subs_label_array',
        'ninja_forms_export_sub_pre_value',
        'ninja_forms_export_sub_value',
        'ninja_forms_export_subs_value_array',
        'ninja_forms_csv_bom',
        'ninja_forms_csv_delimiter',
        'ninja_forms_csv_enclosure',
        'ninja_forms_csv_terminator',
        'ninja_forms_sub_table_row_actions',
        'ninja_forms_csv_delimiter',
        'ninja_forms_csv_enclosure',
        'ninja_forms_csv_terminator',
        'ninja_forms_admin_menu_capabilities',
        'ninja_forms_email_all_fields_array',
        'nf_email_user_values_title',
        'ninja_forms_email_field_label',
        'ninja_forms_email_user_value',
        'ninja_forms_email_field_list',
        'ninja_forms_admin_email_message_wpautop',
        'ninja_forms_admin_email_from',
        'ninja_forms_user_email_message_wpautop',
        'ninja_forms_submission_csv_name',
        'ninja_forms_success_msg',
        'nf_delete_form_capabilities',
        'ninja_forms_field',
        'ninja_forms_display_field_type',
        'ninja_forms_use_post_fields',
        'ninja_forms_list_terms',
        'ninja_forms_display_form_form_data',
        'ninja_forms_admin_subject',
        'ninja_forms_user_subject',
        'ninja_forms_admin_email',
        'ninja_forms_user_email',
        'ninja_forms_save_msg',
        'ninja_forms_display_script_field_data',
        'ninja_forms_display_form_form_data',
        'ninja_forms_enable_credit_card_field',
        'ninja_forms_post_credit_card_field',
        'ninja_forms_credit_card_field_desc_pos',
        'ninja_forms_hide_cc_field',
        'ninja_forms_display_list_options_span_class',
        'nf_import_notification_meta',
        'ninja_forms_labels/timed_submit_error',
        'ninja_forms_form_list_template_function',
        'nf_all_fields_field_value',
        'nf_all_fields_table',
        'nf_before_import_field',
        'nf_delete_field_capabilities',
        'nf_download_all_filename',
        'nf_email_notification_attachment_types',
        'nf_email_notification_attachments',
        'nf_email_notification_process_setting',
        'nf_general_settings_recaptcha',
        'nf_new_field_capabilities',
        'nf_notification_process_setting',
        'nf_step_processing_labels',
        'nf_sub_csv_bom',
        'nf_sub_edit_status',
        'nf_sub_human_time',
        'nf_sub_table_row_actions',
        'nf_sub_table_status',
        'nf_sub_table_user_value_max_items',
        'nf_sub_table_user_value_max_len',
        'nf_sub_title_time',
        'nf_subs_csv_field_label',
        'nf_subs_csv_filename',
        'nf_subs_csv_label_array_before_fields',
        'nf_subs_csv_value_array',
        'nf_subs_export_pre_value',
        'nf_subs_table_qv',
        'nf_success_msg',
        'ninja_forms_admin_email_message_wpautop',
        'ninja_forms_admin_metabox_rte',
        'ninja_forms_ajax_url',
        'ninja_forms_before_import_form',
        'ninja_forms_cont_class',
        'ninja_forms_credit_card_cvc_desc',
        'ninja_forms_credit_card_cvc_label',
        'ninja_forms_credit_card_exp_month_desc',
        'ninja_forms_credit_card_exp_month_label',
        'ninja_forms_credit_card_exp_year_desc',
        'ninja_forms_credit_card_exp_year_label',
        'ninja_forms_credit_card_name_desc',
        'ninja_forms_credit_card_name_label',
        'ninja_forms_credit_card_number_desc',
        'ninja_forms_display_field_class',
        'ninja_forms_display_field_desc_class',
        'ninja_forms_display_field_processing_error_class',
        'ninja_forms_display_fields_wrap_visibility',
        'ninja_forms_display_form_visibility',
        'ninja_forms_display_required_items_class',
        'ninja_forms_display_response_message_class',
        'ninja_forms_display_show_form',
        'ninja_forms_dropdown_open_tag',
        'ninja_forms_dropdown_placeholder',
        'ninja_forms_edit_field_rte',
        'ninja_forms_field_post_process_user_value',
        'ninja_forms_field_pre_process_user_value',
        'ninja_forms_field_process_user_value',
        'ninja_forms_field_shortcode',
        'ninja_forms_field_wrap_class',
        'ninja_forms_fields_wrap_class',
        'ninja_forms_form_class',
        'ninja_forms_form_list_forms',
        'ninja_forms_form_wrap_class',
        'ninja_forms_label_class',
        'ninja_forms_labels/currency_symbol',
        'ninja_forms_labels/date_format',
        'ninja_forms_labels/honeypot_error',
        'ninja_forms_labels/invalid_email',
        'ninja_forms_labels/javascript_error',
        'ninja_forms_labels/password_mismatch',
        'ninja_forms_labels/process_label',
        'ninja_forms_labels/req_div_label',
        'ninja_forms_labels/req_error_label',
        'ninja_forms_labels/req_field_error',
        'ninja_forms_labels/req_field_symbol',
        'ninja_forms_labels/spam_error',
        'ninja_forms_display_fields_array',

    );

    foreach( $hooks as $hook ){
        apply_filters( $hook, '' ); // add_action() is just a wrapper for add_filter(), so use add_filter() for both.
        if( ! isset( $wp_filter[ $hook ] ) || ! $wp_filter[ $hook ] ) continue;
        Ninja_Forms::deprecated_notice( $hook, '3.0', null );
    }
}

function ninja_forms_get_form_by_id( $form_id ) {
    Ninja_Forms::deprecated_notice( $hook, '3.0', null );
    return array();
}