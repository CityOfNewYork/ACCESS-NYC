<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_plugin_settings_defaults', array(

    'date_format' => __( 'm/d/Y', 'ninja-forms' ),
    'currency' => 'USD',

    'recaptcha_site_key' => '',
    'recaptcha_secret_key' => '',
    'recaptcha_lang' => '',

    'delete_on_uninstall' => 0,
    'disable_admin_notices' => 0,

));
