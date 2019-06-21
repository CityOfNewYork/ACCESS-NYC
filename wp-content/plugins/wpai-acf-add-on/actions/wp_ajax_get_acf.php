<?php
use wpai_acf_add_on\acf\groups\GroupFactory;

/**
 *  Render ACF group
 */
function pmai_wp_ajax_get_acf() {

    if (!check_ajax_referer('wp_all_import_secure', 'security', FALSE)) {
        exit(json_encode(array('html' => __('Security check', 'wp_all_import_acf_add_on'))));
    }

    if (!current_user_can('manage_options')) {
        exit(json_encode(array('html' => __('Security check', 'wp_all_import_acf_add_on'))));
    }

    ob_start();

    $acf_groups = PMXI_Plugin::$session->acf_groups;

    $acf_obj = FALSE;

    if (!empty($acf_groups)) {
        foreach ($acf_groups as $key => $group) {
            if ($group['ID'] == $_GET['acf']) {
                $acf_obj = $group;
                break;
            }
        }
    }

    $import = new PMXI_Import_Record();

    if (!empty($_GET['id'])) {
        $import->getById($_GET['id']);
    }

    $is_loaded_template = (!empty(PMXI_Plugin::$session->is_loaded_template)) ? PMXI_Plugin::$session->is_loaded_template : FALSE;

    if ($is_loaded_template) {
        $default = PMAI_Plugin::get_default_import_options();
        $template = new PMXI_Template_Record();
        if (!$template->getById($is_loaded_template)->isEmpty()) {
            $options = (!empty($template->options) ? $template->options : array()) + $default;
        }

    }
    elseif (!$import->isEmpty()) {
        $options = $import->options;
    }
    else {
        $options = PMXI_Plugin::$session->options;
    }

    $group = GroupFactory::create($acf_obj, $options);
    $group->view();

    exit(json_encode(array('html' => ob_get_clean())));
}