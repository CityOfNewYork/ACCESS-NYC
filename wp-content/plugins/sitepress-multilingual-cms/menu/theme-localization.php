<?php

if((!isset($sitepress_settings['existing_content_language_verified']) || !$sitepress_settings['existing_content_language_verified']) || 2 > count($sitepress->get_active_languages())){
    return;
}
$active_languages = $sitepress->get_active_languages();

$mo_file_search = new WPML_MO_File_Search( $sitepress );
if ( ! $mo_file_search->has_mo_file_for_any_language( $active_languages ) ) {
	$mo_file_search->reload_theme_dirs();
}

$locales = $sitepress->get_locale_file_names();
$theme_localization_type = new WPML_Theme_Localization_Type( $sitepress );

?>

<div class="wrap">
    <h2><?php _e('Theme and plugins localization', 'sitepress') ?></h2>

    <h3><?php _e('Select how to translate strings in the theme and plugins','sitepress'); ?></h3>
    <p><?php _e("If your theme and plugins include .mo files with translations, these translations will always be used. This option allows you to provide new and alternative translations for texts in the theme and in plugins using WPML's String Translation.",'sitepress'); ?></p>
    <form name="icl_theme_localization_type" id="icl_theme_localization_type" method="post" action="">    
    <?php wp_nonce_field('icl_theme_localization_type_nonce', '_icl_nonce'); ?>
	    <ul>
		    <?php
		    if ( ! defined( 'WPML_ST_VERSION' ) ) {
			    $icl_st_note = __( "WPML's String Translation module lets you translate the theme, plugins and admin texts. To install it, go to your WPML account, click on Downloads and get WPML String Translation.", 'sitepress' );
			    $st_disabled = 'disabled="disabled" ';
		    } else {
			    $st_disabled = '';
		    }
		    $td_value = isset( $sitepress_settings['gettext_theme_domain_name'] ) ? $sitepress_settings['gettext_theme_domain_name'] : '';
		    if ( ! empty( $sitepress_settings['theme_localization_load_textdomain'] ) ) {
			    $ltd_checked = 'checked="checked" ';
		    } else {
			    $ltd_checked = '';
		    }
		    ?>

            <li>
                <label>
                    <input <?php echo $st_disabled; ?>type="radio" name="icl_theme_localization_type" value="<?php echo WPML_Theme_Localization_Type::USE_ST_AND_NO_MO_FILES ?>" <?php
				    if ( WPML_Theme_Localization_Type::USE_ST_AND_NO_MO_FILES == $sitepress_settings['theme_localization_type'] ): ?>checked="checked"<?php endif; ?> />&nbsp;
				    <?php _e( "Translate the theme and plugins using WPML's String Translation only (don't load .mo files)", 'sitepress' ) ?>
                </label>
			    <?php
			    if ( isset( $icl_st_note ) ) {
				    echo '<br><small><i>' . $icl_st_note . '</i></small>';
			    }
			    ?>
            </li>
		    <li>
			    <label>
				    <input <?php echo $st_disabled; ?>type="radio" name="icl_theme_localization_type" value="<?php echo WPML_Theme_Localization_Type::USE_ST ?>" <?php
				    if ( WPML_Theme_Localization_Type::USE_ST == $sitepress_settings['theme_localization_type'] ): ?>checked="checked"<?php endif; ?> />&nbsp;
                    <?php _e( "Translate the theme and plugins using WPML's String Translation and load .mo files as backup", 'sitepress' ) ?>
			    </label>
			    <?php
			    if ( isset( $icl_st_note ) ) {
				    echo '<br><small><i>' . $icl_st_note . '</i></small>';
			    }
			    ?>
		    </li>
            <li>
                <label>
                    <input type="radio" name="icl_theme_localization_type" value="2" <?php
				    if ( WPML_Theme_Localization_Type::USE_MO_FILES === (int) $sitepress_settings['theme_localization_type'] ): ?>checked="checked"<?php endif; ?> />&nbsp;<?php esc_html_e( "Don't use String Translation to translate the theme and plugins", 'sitepress' ) ?>
                </label>
            </li>
	    </ul>

        <p id="wpml_st_display_strings_scan_notices_box" <?php if ( ! $theme_localization_type->is_st_type() ) echo 'style="display: none;"'; ?> >
            <?php
            if ( class_exists( 'WPML_ST_Themes_And_Plugins_Settings' ) ) {
	            $themes_and_plugins_settings = new WPML_ST_Themes_And_Plugins_Settings();
	            $display_strings_scan_notices_checked = checked( true, $themes_and_plugins_settings->must_display_notices(), false );
            ?>
            <input type="checkbox" id="wpml_st_display_strings_scan_notices" name="wpml_st_display_strings_scan_notices" value="1" <?php echo $display_strings_scan_notices_checked; ?>>
            <label for="wpml_st_display_strings_scan_notices"><?php _e( 'Show an alert when activating plugins and themes, to scan for new strings', 'wpml-string-translation' ) ?></label>
            <?php } ?>
        </p>
    <p>
        <input class="button" name="save" value="<?php echo __('Save','sitepress') ?>" type="submit" />        
        <span style="display:none" class="icl_form_errors icl_form_errors_1"><?php _e('Please enter a value for the textdomain.', 'sitepress'); ?></span>
    </p>
    <img src="<?php echo ICL_PLUGIN_URL ?>/res/img/question-green.png" width="29" height="29" alt="need help" align="left" /><p style="margin-top:14px;">&nbsp;<a href="https://wpml.org/?page_id=2717"><?php _e('Theme localization instructions', 'sitepress')?> &raquo;</a></p>
    </form>

    <?php do_action('icl_custom_localization_type'); ?>
    
    
    <?php do_action('icl_menu_footer'); ?>
               
</div>
