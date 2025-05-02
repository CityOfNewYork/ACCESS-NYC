<?php

use WPML\UrlHandling\WPLoginUrlConverter;
use WPML\AdminLanguageSwitcher\AdminLanguageSwitcher;

/**
 * @package wpml-core
 * @used-by SitePress::ajax_setup
 */
global $wpdb, $sitepress, $sitepress_settings, $wp_rewrite;
/** @var SitePress $this */

$request = filter_input( INPUT_POST, 'icl_ajx_action' );
$request = $request ? $request : filter_input( INPUT_GET, 'icl_ajx_action' );
switch ( $request ) {
	case 'health_check':
		icl_set_setting( 'ajx_health_checked', true, true );
		exit;
	case 'get_browser_language':
		$http_accept_language            = filter_var( $_SERVER['HTTP_ACCEPT_LANGUAGE'], FILTER_SANITIZE_SPECIAL_CHARS );
		$accepted_languages              = explode( ';', $http_accept_language );
		$default_accepted_language       = $accepted_languages[0];
		$default_accepted_language_codes = explode( ',', $default_accepted_language );
		wp_send_json_success( $default_accepted_language_codes );
}

$request = wpml_get_authenticated_action();

function user_is_admin_or_exit() {
	if ( ! WPML\LIB\WP\User::currentUserIsAdmin() ) {
		wp_die( 'Unauthorized', 403 );
	}
}

function user_is_manager_or_exit() {
	if ( ! WPML\LIB\WP\User::currentUserIsTranslationManagerOrHigher() ) {
		wp_die( 'Unauthorized', 403 );
	}
}

function user_is_translator_or_exit() {
	if ( ! WPML\LIB\WP\User::currentUserIsTranslatorOrHigher() ) {
		wp_die( 'Unauthorized', 403 );
	}
}

function user_can_edit_post_or_exit() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Unauthorized', 403 );
	}
}


$iclsettings      = $this->get_settings();
$default_language = $this->get_default_language();

switch ( $request ) {
	case 'icl_admin_language_options':
		user_is_admin_or_exit();

		$iclsettings['admin_default_language'] = $_POST['icl_admin_default_language'];
		$this->save_settings( $iclsettings );
		echo 1;
		break;
	case 'icl_page_sync_options':
		user_is_admin_or_exit();

		$iclsettings['sync_page_ordering']          = @intval( $_POST['icl_sync_page_ordering'] );
		$iclsettings['sync_page_parent']            = @intval( $_POST['icl_sync_page_parent'] );
		$iclsettings['sync_page_template']          = @intval( $_POST['icl_sync_page_template'] );
		$iclsettings['sync_comment_status']         = @intval( $_POST['icl_sync_comment_status'] );
		$iclsettings['sync_ping_status']            = @intval( $_POST['icl_sync_ping_status'] );
		$iclsettings['sync_sticky_flag']            = @intval( $_POST['icl_sync_sticky_flag'] );
		$iclsettings['sync_password']               = @intval( $_POST['icl_sync_password'] );
		$iclsettings['sync_private_flag']           = @intval( $_POST['icl_sync_private_flag'] );
		$iclsettings['sync_post_format']            = @intval( $_POST['icl_sync_post_format'] );
		$iclsettings['sync_delete']                 = @intval( $_POST['icl_sync_delete'] );
		$iclsettings['sync_delete_tax']             = @intval( $_POST['icl_sync_delete_tax'] );
		$iclsettings['sync_post_taxonomies']        = @intval( $_POST['icl_sync_post_taxonomies'] );
		$iclsettings['sync_post_date']              = @intval( $_POST['icl_sync_post_date'] );
		$iclsettings['sync_comments_on_duplicates'] = @intval( $_POST['icl_sync_comments_on_duplicates'] );
		$this->save_settings( $iclsettings );

		$wpml_page_builder_options = new WPML_Page_Builder_Settings();

		if ( array_key_exists( 'wpml_pb_translate_raw_html', $_POST ) ) {
			$wpml_page_builder_options->set_raw_html_translatable(
				filter_var( $_POST['wpml_pb_translate_raw_html'], FILTER_VALIDATE_INT )
			);
		} else {
			$wpml_page_builder_options->set_raw_html_translatable( 0 );
		}

		$wpml_page_builder_options->save();

		echo 1;
		break;
	case 'icl_login_page_translation':
		user_is_admin_or_exit();

		$translateLoginPageIsEnabled = get_option( WPLoginUrlConverter::SETTINGS_KEY );
		if ( ! $translateLoginPageIsEnabled && filter_input( INPUT_POST, 'login_page_translation', FILTER_VALIDATE_BOOLEAN ) ) {
			AdminLanguageSwitcher::enable();
		}

		WPLoginUrlConverter::saveState(
			(bool) filter_input( INPUT_POST, 'login_page_translation', FILTER_VALIDATE_INT )
		);

		AdminLanguageSwitcher::saveState(
			(bool) filter_input( INPUT_POST, 'show_login_page_language_switcher', FILTER_VALIDATE_INT )
		);

		echo 1;
		break;
	case 'language_domains':
		user_is_admin_or_exit();

		$language_domains_helper = new WPML_Lang_Domains_Box( $this );
		echo $language_domains_helper->render();
		break;
	case 'dismiss_help':
		user_is_admin_or_exit();

		icl_set_setting( 'dont_show_help_admin_notice', true );
		icl_save_settings();
		break;
	case 'dismiss_upgrade_notice':
		user_is_admin_or_exit();

		icl_set_setting( 'hide_upgrade_notice', implode( '.', array_slice( explode( '.', ICL_SITEPRESS_VERSION ), 0, 3 ) ) );
		icl_save_settings();
		break;
	case 'toggle_show_translations':
		user_is_admin_or_exit();

		icl_set_setting( 'show_translations_flag', intval( ! wpml_get_setting( 'show_translations_flag', true ) ) );
		icl_save_settings();
		break;
	case 'icl_promote_form':
		user_is_admin_or_exit();

		icl_set_setting( 'promote_wpml', @intval( $_POST['icl_promote'] ) );
		icl_save_settings();
		echo '1|';
		break;
	case 'icl_st_track_strings':
		user_is_manager_or_exit();

		foreach ( $_POST['icl_st'] as $k => $v ) {
			$iclsettings['st'][ $k ] = $v;
		}
		if ( array_key_exists( 'st', $iclsettings ) && array_key_exists( 'hl_color', $iclsettings['st'] ) && ! wpml_is_valid_hex_color( $iclsettings['st']['hl_color'] ) ) {
			$iclsettings['st']['hl_color'] = '#FFFF00';
		}
		if ( isset( $iclsettings ) ) {
			$this->save_settings( $iclsettings );
		}

		do_action( 'wpml_st_strings_tracking_option_saved', (int) $_POST['icl_st']['track_strings'] );

		echo 1;
		break;
	case 'icl_st_more_options':
		user_is_manager_or_exit();

		$iclsettings['st']['translated-users'] = ! empty( $_POST['users'] ) ? array_keys( $_POST['users'] ) : [];
		$this->save_settings( $iclsettings );
		if ( ! empty( $iclsettings['st']['translated-users'] ) ) {
			$sitepress_settings['st']['translated-users'] = $iclsettings['st']['translated-users'];
			icl_st_register_user_strings_all();
		}
		echo 1;
		break;
	case 'icl_hide_languages':
		user_is_admin_or_exit();

		$iclsettings['hidden_languages'] = empty( $_POST['icl_hidden_languages'] ) ? [] : $_POST['icl_hidden_languages'];
		$this->set_setting( 'hidden_languages', [] ); // reset current value
		$active_languages = $this->get_active_languages();
		if ( ! empty( $iclsettings['hidden_languages'] ) ) {
			if ( 1 == count( $iclsettings['hidden_languages'] ) ) {
				$out = sprintf(
					__( '%s is currently hidden to visitors.', 'sitepress' ),
					$active_languages[ $iclsettings['hidden_languages'][0] ]['display_name']
				);
			} else {
				foreach ( $iclsettings['hidden_languages'] as $l ) {
					$_hlngs[] = $active_languages[ $l ]['display_name'];
				}
				$hlangs = join( ', ', $_hlngs );
				$out    = sprintf( __( '%s are currently hidden to visitors.', 'sitepress' ), $hlangs );
			}
			$out .= ' ' . sprintf(
				__( 'You can enable its/their display for yourself, in your <a href="%s">profile page</a>.', 'sitepress' ),
				'profile.php#wpml'
			);
		} else {
			$out = __( 'All languages are currently displayed.', 'sitepress' );
		}
		$this->save_settings( $iclsettings );
		echo '1|' . $out;
		break;
	case 'icl_adjust_ids':
		user_is_admin_or_exit();

		$iclsettings['auto_adjust_ids'] = @intval( $_POST['icl_adjust_ids'] );
		$this->save_settings( $iclsettings );
		echo '1|';
		break;
	case 'icl_automatic_redirect':
		user_is_admin_or_exit();

		if ( ! isset( $_POST['icl_remember_language'] ) || $_POST['icl_remember_language'] < 24 ) {
			$_POST['icl_remember_language'] = 24;
		}
		$iclsettings['automatic_redirect'] = @intval( $_POST['icl_automatic_redirect'] );
		$iclsettings['remember_language']  = @intval( $_POST['icl_remember_language'] );
		$this->save_settings( $iclsettings );
		echo '1|';
		break;
	case 'icl_troubleshooting_more_options':
		user_is_admin_or_exit();

		$iclsettings['troubleshooting_options'] = $_POST['troubleshooting_options'];
		$this->save_settings( $iclsettings );
		echo '1|';
		break;
	case 'reset_languages':
		user_is_admin_or_exit();

		$setup_instance = wpml_get_setup_instance();
		$setup_instance->reset_language_data();

		$wpml_localization = new WPML_Download_Localization( $sitepress->get_active_languages(), $sitepress->get_default_language() );
		$wpml_localization->download_language_packs();
		$wpml_languages_notices = new WPML_Languages_Notices( wpml_get_admin_notices() );
		$wpml_languages_notices->missing_languages( $wpml_localization->get_not_founds() );
		break;
	case 'icl_custom_tax_sync_options':
		user_is_manager_or_exit();

		$new_options      = ! empty( $_POST['icl_sync_tax'] ) ? $_POST['icl_sync_tax'] : [];
		$unlocked_options = ! empty( $_POST['icl_sync_tax_unlocked'] ) ? $_POST['icl_sync_tax_unlocked'] : [];
		/** @var WPML_Settings_Helper $settings_helper */
		$settings_helper = wpml_load_settings_helper();
		$settings_helper->update_taxonomy_unlocked_settings( $unlocked_options );
		$settings_helper->update_taxonomy_sync_settings( $new_options );
		echo '1|';
		break;
	case 'icl_custom_posts_sync_options':
		user_is_manager_or_exit();

		$new_options      = ! empty( $_POST['icl_sync_custom_posts'] ) ? $_POST['icl_sync_custom_posts'] : [];
		$unlocked_options = ! empty( $_POST['icl_sync_custom_posts_unlocked'] ) ? $_POST['icl_sync_custom_posts_unlocked'] : [];
		/** @var WPML_Settings_Helper $settings_helper */
		$settings_helper = wpml_load_settings_helper();
		$settings_helper->update_cpt_unlocked_settings( $unlocked_options );
		$settings_helper->update_cpt_sync_settings( $new_options );
		$customPostTypes = ( new WPML_Post_Types( $sitepress ) )->get_translatable_and_readonly();
		echo '1|';
		break;
	case 'copy_from_original':
		user_is_translator_or_exit();

		/*
		 * apply filtering as to add further elements
		 * filters will have to like as such
		 * add_filter('wpml_copy_from_original_custom_fields', 'my_copy_from_original_fields');
		 *
		 * function my_copy_from_original_fields( $elements ) {
		 *  $custom_field = 'editor1';
		 *  $elements[ 'customfields' ][ $custom_fields ] = array(
		 *    'editor_name' => 'custom_editor_1',
		 *    'editor_type' => 'editor',
		 *    'value'       => 'test'
		 *  );
		 *
		 *  $custom_field = 'editor2';
		 *  $elements[ 'customfields' ][ $custom_fields ] = array(
		 *    'editor_name' => 'textbox1',
		 *    'editor_type' => 'text',
		 *    'value'       => 'testtext'
		 *  );
		 *
		 *  return $elements;
		 * }
		 * This filter would result in custom_editor_1 being populated with the value "test"
		 * and the textfield with id #textbox1 to be populated with "testtext".
		 * editor type is always either text when populating general fields or editor when populating
		 * a wp editor. The editor id can be either judged from the arguments used in the wp_editor() call
		 * or from looking at the tinyMCE.Editors object that the custom post type's editor sends to the browser.
		 */
		$content_type = filter_input( INPUT_POST, 'content_type' );
		$excerpt_type = filter_input( INPUT_POST, 'excerpt_type' );
		$trid         = filter_input( INPUT_POST, 'trid' );
		$lang         = filter_input( INPUT_POST, 'lang' );
		echo wp_json_encode( WPML_Post_Edit_Ajax::copy_from_original_fields( $content_type, $excerpt_type, $trid, $lang ) );
		break;
	case 'save_user_preferences':
		user_is_translator_or_exit();

		$user_preferences = $this->get_user_preferences();
		$this->set_user_preferences( array_merge_recursive( $user_preferences, $_POST['user_preferences'] ) );
		$this->save_user_preferences();
		break;
	case 'wpml_cf_translation_preferences':
		user_is_manager_or_exit();

		if ( empty( $_POST[ WPML_POST_META_SETTING_INDEX_SINGULAR ] ) ) {
			echo '<span style="color:#FF0000;">'
				 . __( 'Error: No custom field', 'sitepress' ) . '</span>';
			die();
		}
		$_POST[ WPML_POST_META_SETTING_INDEX_SINGULAR ] = @strval( $_POST[ WPML_POST_META_SETTING_INDEX_SINGULAR ] );
		if ( ! isset( $_POST['translate_action'] ) ) {
			echo '<span style="color:#FF0000;">'
				 . __( 'Error: Please provide translation action', 'sitepress' ) . '</span>';
			die();
		}
		$_POST['translate_action'] = @intval( $_POST['translate_action'] );
		if ( defined( 'WPML_TM_VERSION' ) ) {
			global $iclTranslationManagement;
			if ( ! empty( $iclTranslationManagement ) ) {
				$iclTranslationManagement->settings[ WPML_POST_META_SETTING_INDEX_PLURAL ][ $_POST[ WPML_POST_META_SETTING_INDEX_SINGULAR ] ] = $_POST['translate_action'];
				$iclTranslationManagement->save_settings();
				echo '<strong><em>' . __( 'Settings updated', 'sitepress' ) . '</em></strong>';
			} else {
				echo '<span style="color:#FF0000;">'
					 . __( 'Error: WPML Translation Management plugin not initiated', 'sitepress' )
					 . '</span>';
			}
		} else {
			echo '<span style="color:#FF0000;">'
				 . __( 'Error: Please activate WPML Translation Management plugin', 'sitepress' )
				 . '</span>';
		}
		break;
	case 'icl_seo_options':
		user_is_admin_or_exit();

		$seo = $sitepress->get_setting( 'seo', [] );

		$seo['head_langs']                  = isset( $_POST['icl_seo_head_langs'] ) ? (int) $_POST['icl_seo_head_langs'] : 0;
		$seo['canonicalization_duplicates'] = isset( $_POST['icl_seo_canonicalization_duplicates'] ) ? (int) $_POST['icl_seo_canonicalization_duplicates'] : 0;
		$seo['head_langs_priority']         = isset( $_POST['wpml_seo_head_langs_priority'] ) ? (int) $_POST['wpml_seo_head_langs_priority'] : 1;

		$sitepress->set_setting( 'seo', $seo, true );
		echo '1|';
		break;
	case 'connect_translations': // This is used by the "Connect Translations" dialog.
		user_can_edit_post_or_exit();

		$new_trid      = $_POST['new_trid'];
		$post_type     = $_POST['post_type'];
		$post_id       = $_POST['post_id'];
		$set_as_source = $_POST['set_as_source'];
		$element_type  = 'post_' . $post_type;

		$language_details = $sitepress->get_element_language_details( $post_id, $element_type );

		if ( $set_as_source ) {

			$wpdb->update(
				$wpdb->prefix . 'icl_translations',
				[ 'source_language_code' => $language_details->language_code ],
				[
					'trid'         => $new_trid,
					'element_type' => $element_type,
				],
				[ '%s' ],
				[ '%d', '%s' ]
			);

			$wpdb->update(
				$wpdb->prefix . 'icl_translations',
				[
					'source_language_code' => null,
					'trid'                 => $new_trid,
				],
				[
					'element_id'   => $post_id,
					'element_type' => $element_type,
				],
				[ '%s', '%d' ],
				[ '%d', '%s' ]
			);

			do_action(
				'wpml_translation_update',
				[
					'type'         => 'update',
					'trid'         => $new_trid,
					'element_type' => $element_type,
					'context'      => 'post',
				]
			);

		} else {
			$original_element_language = $sitepress->get_default_language();
			$trid_elements             = $sitepress->get_element_translations( $new_trid, $element_type );
			if ( $trid_elements ) {
				foreach ( $trid_elements as $trid_element ) {
					if ( $trid_element->original ) {
						$original_element_language = $trid_element->language_code;
						break;
					}
				}
			}

			$wpdb->update(
				$wpdb->prefix . 'icl_translations',
				[
					'source_language_code' => $original_element_language,
					'trid'                 => $new_trid,
				],
				[
					'element_id'   => $post_id,
					'element_type' => $element_type,
				],
				[ '%s', '%d' ],
				[ '%d', '%s' ]
			);


			do_action(
				'wpml_translation_update',
				[
					'type'         => 'update',
					'trid'         => $new_trid,
					'element_id'   => $post_id,
					'element_type' => $element_type,
					'context'      => 'post',
				]
			);

		}
		echo wp_json_encode( true );
		break;
	case 'get_posts_from_trid': // This is used by the "Connect Translations" dialog.
		user_can_edit_post_or_exit();

		$trid      = $_POST['trid'];
		$post_type = $_POST['post_type'];

		$translations = $sitepress->get_element_translations( $trid, 'post_' . $post_type );

		$results = [];
		foreach ( $translations as $language_code => $translation ) {
			$post                 = get_post( $translation->element_id );
			$title                = $post->post_title ? $post->post_title : strip_shortcodes( wp_trim_words( $post->post_content, 50 ) );
			$source_language_code = $translation->source_language_code;
			$results[]            = (object) [
				'language'        => $language_code,
				'title'           => $title,
				'source_language' => $source_language_code,
			];
		}
		echo wp_json_encode( $results );
		break;
	case 'get_orphan_posts': // This is used by the "Connect Translations" dialog.
		user_can_edit_post_or_exit();

		$trid            = $_POST['trid'];
		$post_type       = $_POST['post_type'];
		$source_language = $_POST['source_language'];
		$results         = $sitepress->get_orphan_translations( $trid, $post_type, $source_language );

		echo wp_json_encode( $results );

		break;
	// classes/ATE/Hooks/class-wpml-tm-old-editor.php
	case 'icl_doc_translation_method':
		user_is_translator_or_exit();
		do_action( 'icl_ajx_custom_call', $request, $_REQUEST );
		break;
	// modules/cache-plugins-integration/cache-plugins-integration.php
	case 'wpml_cpi_options':
	case 'wpml_cpi_clear_cache':
		user_is_manager_or_exit();
		do_action( 'icl_ajx_custom_call', $request, $_REQUEST );
		break;
	// inc/translation-management/translation-management.class.php
	case 'assign_translator':
	case 'icl_cf_translation':
	case 'icl_tcf_translation':
	case 'icl_doc_translation_method':
	case 'reset_duplication':
	case 'set_duplication':
		user_is_translator_or_exit();
		do_action( 'icl_ajx_custom_call', $request, $_REQUEST );
		break;
	// inc/translation-proxy/wpml-pro-translation.class.php
	case 'set_pickup_mode':
		user_is_manager_or_exit();
		do_action( 'icl_ajx_custom_call', $request, $_REQUEST );
		break;
	//inc/upgrade-functions/upgrade-2.0.0.php
	case 'wpml_upgrade_2_0_0':
		user_is_manager_or_exit();
		do_action( 'icl_ajx_custom_call', $request, $_REQUEST );
		break;
	//wpml-string-translation/inc/wpml-string-translation.class.php
	case 'icl_st_delete_strings':
		user_is_translator_or_exit();
		do_action( 'icl_ajx_custom_call', $request, $_REQUEST );
		break;
	//wpml-string-translation/classes/slug-translation/class-wpml-slug-translation.php
	case 'icl_slug_translation':
		user_is_translator_or_exit();
		do_action( 'icl_ajx_custom_call', $request, $_REQUEST );
		break;
}
exit;
