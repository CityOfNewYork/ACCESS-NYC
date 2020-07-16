<?php
/**
 * String translation common functions.
 *
 * @package WPML\ST
 * @todo    : Move the functions code to classes and keep the functions as wrappers only
 */

use function WPML\Container\make;
use WPML\ST\Gettext\Hooks;
use WPML\ST\Gettext\HooksFactory;

add_action( 'plugins_loaded', 'icl_st_init' );

function icl_st_init(){
    global $sitepress_settings, $sitepress, $wpdb, $icl_st_err_str, $pagenow, $authordata;

	if ( empty( $sitepress_settings['setup_complete'] ) || ( $pagenow === 'site-new.php' && isset( $_REQUEST['action'] ) && 'add-site' === $_REQUEST['action'] ) ) return;

    add_action('icl_update_active_languages', 'icl_update_string_status_all');
    add_action('update_option_blogname', 'icl_st_update_blogname_actions',5,2);
    add_action('update_option_blogdescription', 'icl_st_update_blogdescription_actions',5,2);

    if(!isset($sitepress_settings['existing_content_language_verified']) || !$sitepress_settings['existing_content_language_verified']){
        return;
    }

    if(!isset($sitepress_settings['st']['strings_per_page'])){
        $sitepress_settings['st']['strings_per_page'] = WPML_ST_DEFAULT_STRINGS_PER_PAGE;
        $sitepress->save_settings($sitepress_settings);
    }elseif(isset($_GET['strings_per_page']) && $_GET['strings_per_page'] > 0){
        $sitepress_settings['st']['strings_per_page'] = $_GET['strings_per_page'];
        $sitepress->save_settings($sitepress_settings);
    }
    if(!isset($sitepress_settings['st']['icl_st_auto_reg'])){
        $sitepress_settings['st']['icl_st_auto_reg'] = 'disable';
        $sitepress->save_settings($sitepress_settings);

	    do_action( 'wpml_st_auto_register_default' );
    }
    if(empty($sitepress_settings['st']['strings_language'])){
        $iclsettings['st']['strings_language'] = $sitepress_settings['st']['strings_language'] = 'en';
        $sitepress->save_settings($iclsettings);
    }

    if(!isset($sitepress_settings['st']['translated-users'])) $sitepress_settings['st']['translated-users'] = array();

	// handle po file upload

	new WPML_PO_Import_Strings_Scripts();
	$po_import_strings = new WPML_PO_Import_Strings();
	$po_import_strings->maybe_import_po_add_strings();
	$icl_st_err_str = $po_import_strings->get_errors();

    //handle po export
    if(isset($_POST['icl_st_pie_e']) && wp_verify_nonce($_POST['_wpnonce'], 'icl_po_export')){
        //force some filters
        if(isset($_GET['status'])) unset($_GET['status']);
        $_GET['show_results']='all';
        if($_POST['icl_st_e_context']){
            $_GET['context'] = $_POST['icl_st_e_context'];
        }

        $_GET['translation_language'] = $_POST['icl_st_e_language'];
        $strings = icl_get_string_translations();
	    if ( ! empty( $strings ) ) {
		    $po = icl_st_generate_po_file( $strings );
	    } else {
		    $po = "";
	    }
	    if(!isset($_POST['icl_st_pe_translations'])){
            $popot = 'pot';
            $poname = $_POST['icl_st_e_context'] ? urlencode($_POST['icl_st_e_context']) : 'all_context';
        }else{
            $popot = 'po';
            $poname = $_GET['context'] . '-' . $_GET['translation_language'];
        }
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=".$poname.'.'.$popot.";");
        header("Content-Length: ". strlen($po));
        echo $po;
        exit(0);
    }

	/**
	 * @todo: Move this class to the standard hooks loader
	 * @var WPML_ST_Blog_Name_And_Description_Hooks $blog_name_and_desc_hooks
	 */
	$blog_name_and_desc_hooks = make( WPML_ST_Blog_Name_And_Description_Hooks::class );
	$blog_name_and_desc_hooks->add_hooks();
	add_filter('widget_title', 'icl_sw_filters_widget_title', 0);  //highest priority
	add_filter('widget_text', 'icl_sw_filters_widget_text', 0); //highest priority

    $widget_groups = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'widget\\_%'");
    foreach($widget_groups as $w){
        add_action('update_option_' . $w->option_name, 'icl_st_update_widget_title_actions', 5, 2);
    }

    add_action('update_option_widget_text', 'icl_st_update_text_widgets_actions', 5, 2);
    add_action( 'update_option_sidebars_widgets', 'wpml_st_init_register_widget_titles' );

    if($icl_st_err_str){
        add_action('admin_notices', 'icl_st_admin_notices');
    }
		if (isset($_REQUEST['string-translated']) && $_REQUEST['string-translated'] == true) {
			add_action('admin_notices', 'icl_st_admin_notices_string_updated');
		}

	$user_fields = new WPML_ST_User_Fields( $sitepress, $authordata );
	$user_fields->init_hooks();
}

function wpml_st_init_register_widget_titles(){

    // create a list of active widgets
    $active_widgets = array();
    $widgets = (array)get_option('sidebars_widgets');

    foreach($widgets as $k=>$w){
        if('wp_inactive_widgets' != $k && $k != 'array_version'){
            if(is_array($widgets[$k]))
            foreach($widgets[$k] as $v){
                $active_widgets[] = $v;
            }
        }
    }
    foreach($active_widgets as $aw){
        $int = preg_match('#-([0-9]+)$#i',$aw, $matches);
        if($int){
            $suffix = $matches[1];
        }else{
            $suffix = 1;
        }
        $name = preg_replace('#-[0-9]+#','',$aw);

        $value = get_option("widget_".$name);
        if(isset($value[$suffix]['title']) && $value[$suffix]['title']){
            $w_title = $value[$suffix]['title'];
        }else{
            $w_title = wpml_get_default_widget_title( $aw);
            $value[$suffix]['title'] = $w_title;
            update_option("widget_".$name, $value);
        }

        if($w_title){
            icl_register_string( WP_Widget_Text_Icl::STRING_DOMAIN, 'widget title - ' . md5( $w_title ), $w_title );
        }
    }
}

function wpml_get_default_widget_title($id){
    if(preg_match('#archives(-[0-9]+)?$#i',$id)){
        $w_title = 'Archives';
    }elseif(preg_match('#categories(-[0-9]+)?$#i',$id)){
        $w_title = 'Categories';
    }elseif(preg_match('#calendar(-[0-9]+)?$#i',$id)){
        $w_title = 'Calendar';
    }elseif(preg_match('#links(-[0-9]+)?$#i',$id)){
        $w_title = 'Links';
    }elseif(preg_match('#meta(-[0-9]+)?$#i',$id)){
        $w_title = 'Meta';
    }elseif(preg_match('#pages(-[0-9]+)?$#i',$id)){
        $w_title = 'Pages';
    }elseif(preg_match('#recent-posts(-[0-9]+)?$#i',$id)){
        $w_title = 'Recent Posts';
    }elseif(preg_match('#recent-comments(-[0-9]+)?$#i',$id)){
        $w_title = 'Recent Comments';
    }elseif(preg_match('#rss-links(-[0-9]+)?$#i',$id)){
        $w_title = 'RSS';
    }elseif(preg_match('#search(-[0-9]+)?$#i',$id)){
        $w_title = 'Search';
    }elseif(preg_match('#tag-cloud(-[0-9]+)?$#i',$id)){
        $w_title = 'Tag Cloud';
    }else{
        $w_title = false;
    }
    return $w_title;
}

/**
 * Registers a string for translation
 *
 * @param string|array $context           The context for the string
 * @param string       $name              A name to help the translator understand what’s being translated
 * @param string       $value             The string value
 * @param bool         $allow_empty_value This param is not being used
 * @param string       $source_lang       The language of the registered string. Defaults to 'en'
 *
 * @return int string_id of the just registered string or the id found in the database corresponding to the
 *             input parameters
 */
function icl_register_string( $context, $name, $value, $allow_empty_value = false, $source_lang = '' ) {
	global $WPML_String_Translation;

    if ( ! $name ) {
        $name = md5( $value );
    }

	/**
	 * @var WPML_Register_String_Filter $admin_string_filter
	 * @var WPML_String_Translation $WPML_String_Translation
	 */
	$strings_language    = $WPML_String_Translation->get_current_string_language( $name );
	$admin_string_filter = $WPML_String_Translation->get_admin_string_filter( $strings_language );

	if ( $admin_string_filter ) {
        $string_id = $admin_string_filter->register_string( $context, $name, $value, $allow_empty_value, $source_lang );
    } else {
        $string_id = null;
    }

	return $string_id;
}

/**
 * @since      unknown
 * @deprecated 3.2 use 'wpml_register_string_for_translation' action instead.
 */
add_filter('register_string_for_translation', 'icl_register_string', 10, 4);


/**
 * Registers a string for translation
 *
 * @api
 *
 * @param string $context The context for the string
 * @param string $name A name to help the translator understand what’s being translated
 * @param string $value The string value
 * @param bool $allow_empty_value This param is not being used
 * @param string $source_lang_code
 */
function wpml_register_single_string_action( $context, $name, $value, $allow_empty_value = false, $source_lang_code = '' ) {
    icl_register_string( $context, $name, $value, $allow_empty_value, $source_lang_code );
}

/**
 * @since 3.2
 * @api
 */
add_action('wpml_register_single_string', 'wpml_register_single_string_action', 10, 5);

/**
 * @param string|array $context
 * @param string       $name
 * @param bool         $value
 * @param bool         $allow_empty_value
 * @param null|bool    $has_translation
 * @param null|string  $target_lang
 *
 * @return bool|string
 */
function icl_translate( $context, $name, $value = false, $allow_empty_value = false, &$has_translation = null, $target_lang = null ) {
	static $translate_wpml_string;

	if ( ! $translate_wpml_string ) {
		/** @var \WPML\ST\TranslateWpmlString $translate_wpml_string */
		$translate_wpml_string = \WPML\Container\make( \WPML\ST\TranslateWpmlString::class );
		$translate_wpml_string->init();
	}

	return $translate_wpml_string->translate( $context, $name, $value, $allow_empty_value, $has_translation, $target_lang );
}

/**
 * @return bool
 */
function wpml_st_is_requested_blog() {
	return ! ( is_multisite() && ms_is_switched() )
		   || (int)$GLOBALS['blog_id'] === (int)end( $GLOBALS['_wp_switched_stack'] );
}

/**
 * @param string $value
 * @param mixed $context
 * @param string $name
 *
 * @return string
 */
function wpml_get_string_current_translation( $value, $context, $name ) {
	$string_id = icl_get_string_id( $value, $context, $name );

	/** @var WPML_String_Translation $WPML_String_Translation */
	global $WPML_String_Translation;
	$current_lang = $WPML_String_Translation->get_current_string_language( $name );
	$translation        = icl_get_string_by_id( $string_id, $current_lang );

	$value = $translation ? $translation : $value;

	return $value;
}

function icl_st_is_registered_string( $context, $name ) {
    global $wpdb;
    static $cache = array();

    if ( isset( $cache[ $context ][ $name ] ) ) {
        $string_id = $cache[ $context ][ $name ];
    } else {
        $string_id                  = $wpdb->get_var( $wpdb->prepare( "
          SELECT id
          FROM {$wpdb->prefix}icl_strings
          WHERE context = %s
              AND name = %s
          LIMIT 1", $context, $name ) );
        $cache[ $context ][ $name ] = $string_id;
    }

    return $string_id;
}

function icl_st_string_has_translations($context, $name){
    global $wpdb;
    $sql = $wpdb->prepare(
        "
        SELECT COUNT(st.id) 
        FROM {$wpdb->prefix}icl_string_translations st 
        JOIN {$wpdb->prefix}icl_strings s ON s.id=st.string_id
        WHERE s.context = %s AND s.name = %s",
        $context,
        $name
    );

    return $wpdb->get_var($sql);
}

function icl_update_string_status( $string_id ) {
    global $wpdb;

    $string = new WPML_ST_String( $string_id, $wpdb );

    return $string->update_status();
}

function icl_update_string_status_all(){
    global $wpdb, $sitepress;

    $updater = new WPML_ST_Bulk_Update_Strings_Status( $wpdb, array_keys( $sitepress->get_active_languages() ) );
    $updater->run();
}

/**
 * @param string $context
 * @param string $name
 */
function icl_unregister_string( $context, $name ) {
	global $wpdb;

	$string_id = $wpdb->get_var(
		$wpdb->prepare( "
				SELECT id FROM {$wpdb->prefix}icl_strings
            	WHERE context=%s AND name=%s
        	",
			$context,
			$name
		)
	);

	if ( $string_id ) {
		/**
		 * This action is is fired before several strings are deleted at once.
		 *
		 * @param array Here containing only the single string that is deleted.
		 *
		 * @since 3.0.0
		 *
		 */
		do_action( 'wpml_st_before_remove_strings', [ $string_id ] );

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_strings WHERE id=%d", $string_id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_string_translations WHERE string_id=%d",
		                              $string_id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_string_positions WHERE string_id=%d",
		                              $string_id ) );
    }

	/**
	 * This action is is fired when a string is deleted.
	 *
	 * @param array $string_id
	 */
    do_action( 'icl_st_unregister_string', $string_id );
}

/**
 * @param array $string_ids
 */
function wpml_unregister_string_multi( array $string_ids ) {
	global $wpdb;

	/**
	 * This action is is fired before several strings are deleted at once.
	 *
	 * @param array $string_ids
	 *
	 * @since 3.0.0
	 *
	 */
	do_action( 'wpml_st_before_remove_strings', $string_ids );

	$str = wpml_prepare_in( $string_ids, '%d' );
	$wpdb->query( "
		DELETE s.*, t.* FROM {$wpdb->prefix}icl_strings s
		LEFT JOIN {$wpdb->prefix}icl_string_translations t ON s.id = t.string_id
        WHERE s.id IN ({$str})
    " );
	$wpdb->query( "DELETE FROM {$wpdb->prefix}icl_string_positions WHERE string_id IN ({$str})" );

	/**
	 * This action is is fired when several strings are deleted at once.
	 *
	 * @param array $string_ids
	 */
	do_action( 'icl_st_unregister_string_multi', $string_ids );
}

/**
 * @since      unknown
 * @deprecated 3.2 use 'wpml_translate_string' filter instead.
 */
function translate_string_filter( $original_value, $context, $name, $has_translation = null, $disable_auto_register = false, $language_code = null ) {
	return icl_t( $context, $name, $original_value, $has_translation, $disable_auto_register, $language_code );
}

/**
 * @since      unknown
 * @deprecated 3.2 use 'wpml_translate_single_string' filter instead.
 */
add_filter('translate_string', 'translate_string_filter', 10, 5);

/**
 * Retrieve a string translation
 * Looks for a string with matching $context and $name.
 * If it finds it, it looks for translation in the current language or the language specified
 * If a translation exists, it will return it. Otherwise, it will return the original string.
 *
 * @api
 *
 * @param string|bool $original_value           The string's original value
 * @param string      $context                  The string's registered context
 * @param string      $name                     The string's registered name
 * @param null|string $language_code            Return the translation in this language
 *                                              Default is NULL which returns the current language
 * @param bool|null   $has_translation          Currently unused. Defaults to NULL
 *
 * @return string
 */
function wpml_translate_single_string_filter( $original_value, $context, $name, $language_code = null, $has_translation = null ) {
	$result = $original_value;
	if ( is_string( $name ) ) {
		$result = icl_translate( $context, $name, $original_value, false, $has_translation, $language_code );
	}

	return $result;
}

/**
 * @api
 * @since 3.2
 */
add_filter('wpml_translate_single_string', 'wpml_translate_single_string_filter', 10, 6);

/**
 * Retrieve a string translation
 * Looks for a string with matching $context and $name.
 * If it finds it, it looks for translation in the current language or the language specified
 * If a translation exists, it will return it. Otherwise, it will return the original string.
 *
 * @param string|bool $original_value           The string's original value
 * @param string      $context                  The string's registered context
 * @param string      $name                     The string's registered name
 * @param bool|null   $has_translation          Currently unused. Defaults to NULL
 * @param bool        $disable_auto_register    Currently unused. Set to false in calling icl_translate
 * @param null|string $language_code            Return the translation in this language
 *                                              Default is NULL which returns the current language
 *
 * @return string
 */
function icl_t( $context, $name, $original_value = false, &$has_translation = null, $disable_auto_register = false, $language_code = null ) {

	return icl_translate( $context, $name, $original_value, false, $has_translation, $language_code );
}

/**
 * @deprecated since WPML ST 3.0.0
 *
 * @param string $name
 *
 * @return bool
 */
function is_translated_admin_string( $name ) {
	return WPML_ST_Blog_Name_And_Description_Hooks::is_string( $name );
}

/**
 * Helper function for icl_t()
 * @param array $result
 * @param string $original_value
 * @return boolean
 */
function _icl_is_string_change($result, $original_value) {

	if ($result == false) {
		return false;
	}

	if (!isset($result['value'])) {
		return false;
	}
	return (
                $result['translated'] && $result['original'] != $original_value ||
                !$result['translated'] && $result['value'] != $original_value
            );
}

function icl_add_string_translation( $string_id, $language, $value = null, $status = false, $translator_id = null, $translation_service = null, $batch_id = null ) {
	global $wpdb;

	$string = new WPML_ST_String( $string_id, $wpdb );

	$translated_string_id = $string->set_translation( $language, $value, $status, $translator_id, $translation_service, $batch_id );

	return $translated_string_id;
}

/**
 * Updates the string translation for an admin option
 *
 * @global SitePress               $sitepress
 * @global WPML_String_Translation $WPML_String_Translation
 *
 * @param string                   $option_name
 * @param string                   $language
 * @param string                   $new_value
 * @param int|bool                 $status
 * @param int                      $translator_id
 *
 * @return boolean|mixed
 */
function icl_update_string_translation(
	$option_name,
	$language,
	$new_value = null,
	$status = false,
	$translator_id = null
) {
	/** @var WPML_String_Translation $WPML_String_Translation */
	global $WPML_String_Translation;

	return $WPML_String_Translation->get_admin_option( $option_name, $language )
	                               ->update_option( '', $new_value, $status,
		                               $translator_id, 0 );
}

/**
 * @param $string
 * @param $context
 * @param bool|false $name
 *
 * @return int
 */
function icl_get_string_id( $string, $context, $name = false ) {
    return WPML\Container\make( 'WPML_ST_String_Factory' )->get_string_id( $string, $context, $name );
}

function icl_get_string_translations() {
	global $sitepress, $wpdb, $wp_query;

	$WPML_ST_Strings = new WPML_ST_Strings($sitepress, $wpdb, $wp_query);
	return $WPML_ST_Strings->get_string_translations();
}

/** *
 * @param int          $string_id     ID of string in icl_strings DB table
 * @param string|false $language_code false, or language code
 *
 * @return string|false
 */
function icl_get_string_by_id( $string_id, $language_code = false ) {
	global $wpdb, $sitepress_settings;

	$result = $wpdb->get_row( $wpdb->prepare(
		"SELECT value, language FROM {$wpdb->prefix}icl_strings WHERE id=%d", $string_id
	) );

	if ( $result && ( ! $language_code || $language_code === $result->language ) ) {
		return $result->value;
	}

	if ( ! $language_code ) {
		if ( isset( $sitepress_settings['st']['strings_language'] ) ) {
			$language_code = $sitepress_settings['st']['strings_language'];
		} else {
			return false;
		}
	}

	$translations = icl_get_string_translations_by_id( $string_id );
	if ( isset( $translations[ $language_code ] ) ) {
		return $translations[ $language_code ]['value'];
	}

	return false;
}

function icl_get_string_translations_by_id( $string_id ) {
	global $wpdb;

	$found = false;
	$translations = WPML_Non_Persistent_Cache::get( $string_id, 'icl_get_string_translations_by_id', $found );
	if ( ! $found ) {
		$translations = array();

		if ( $string_id ) {
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT language, value, status FROM {$wpdb->prefix}icl_string_translations WHERE string_id=%d", $string_id ) );
			foreach ( $results as $row ) {
				$translations[ $row->language ] = array( 'value' => $row->value, 'status' => $row->status );
			}
		}
		WPML_Non_Persistent_Cache::set( $string_id, $translations, 'icl_get_string_translations_by_id' );
	}

    return $translations;

}

function icl_get_strings_tracked_in_pages($string_translations){
    global $wpdb;
    // get string position in page - if found
    $found_strings = $strings_in_page = array();
    foreach(array_keys((array)$string_translations) as $string_id){
        $found_strings[] = $string_id;
    }
    if($found_strings){
        $res = $wpdb->get_results("
            SELECT kind, string_id  FROM {$wpdb->prefix}icl_string_positions 
            WHERE string_id IN (" . wpml_prepare_in($found_strings, '%d' ) . ")");
        foreach($res as $row){
            $strings_in_page[$row->kind][$row->string_id] = true;
        }
    }
    return $strings_in_page;
}

function icl_sw_filters_widget_title($val){
	$val = icl_translate( WP_Widget_Text_Icl::STRING_DOMAIN, 'widget title - ' . md5( $val ) , $val );
  return $val;
}

function icl_sw_filters_widget_text($val){
	$val = icl_translate( WP_Widget_Text_Icl::STRING_DOMAIN, 'widget body - ' . md5( $val ) , $val );
  return $val;
}

/**
 * @deprecated since WPML ST 3.0.0
 *
 * @param string $translation String This parameter is not important to the filter since we filter before other filters.
 * @param string $text
 * @param string|array $domain
 * @param bool|string $name
 *
 * @return string
 * @throws Auryn\InjectionException Auryn Exception.
 */
function icl_sw_filters_gettext( $translation, $text, $domain, $name = false ) {
	static $gettext_hooks;

	if ( null === $gettext_hooks ) {
		/**
		 * @var	HooksFactory $factory
		 * @var Hooks|null   $gettext_hooks
		 */
		$factory       = WPML\Container\make( HooksFactory::class );
		$gettext_hooks = $factory->create();
		$gettext_hooks = $gettext_hooks ?: false;
	}

	return $gettext_hooks ? $gettext_hooks->gettext_filter( $translation, $text, $domain, $name ) : $translation;
}

/**
 * @deprecated since WPML ST 3.0.0
 *
 * @param $sitepress_settings
 *
 * @return bool
 */
function icl_sw_must_track_strings( $sitepress_settings ) {
	/** @var Hooks $gettext_hooks */
	$gettext_hooks = WPML\Container\make( Hooks::class );

	return $gettext_hooks->must_track_strings();
}

function icl_st_track_string( $text, $domain, $kind = ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE ) {

    if (is_multisite() && ms_is_switched()) {
        return;
    }

    require_once dirname(__FILE__) . '/gettext/wpml-string-scanner.class.php';

    static $string_scanner = null;
    if ( ! $string_scanner ) {
        try {
            $wp_filesystem = wpml_get_filesystem_direct();
            $string_scanner = new WPML_String_Scanner( $wp_filesystem, new WPML_ST_File_Hashing() );
        } catch( Exception $e ) {
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
    }

    if ( $string_scanner ) {
        $string_scanner->track_string( $text, $domain, $kind );
    }
}

/**
 * @deprecated since WPML ST 3.0.0
 *
 * @param string $translation
 * @param string $text
 * @param string $_gettext_context
 * @param string $domain
 *
 * @return string
 * @throws Auryn\InjectionException Auryn Exception.
 */
function icl_sw_filters_gettext_with_context( $translation, $text, $_gettext_context, $domain ){
    if ( $_gettext_context ) {
        return icl_sw_filters_gettext( $translation, $text, array( 'domain' => $domain, 'context' => $_gettext_context ) );
    } else {
        return icl_sw_filters_gettext( $translation, $text, $domain );
    }
}

/**
 * @deprecated since WPML ST 3.0.0
 *
 * @param string       $translation
 * @param string       $single
 * @param string       $plural
 * @param string       $number
 * @param string|array $domain
 * @param string|false $_gettext_context
 *
 * @return string
 * @throws Auryn\InjectionException Auryn Exception.
 */
function icl_sw_filters_ngettext( $translation, $single, $plural, $number, $domain, $_gettext_context = false ) {
    if ( $number == 1 ){
        return icl_sw_filters_gettext_with_context( $translation, $single, $_gettext_context, $domain );
    }else{
        return icl_sw_filters_gettext_with_context( $translation, $plural, $_gettext_context, $domain );
    }
}

/**
 * @deprecated since WPML ST 3.0.0
 *
 * @param string $translation
 * @param string $single
 * @param string $plural
 * @param string $number
 * @param string $_gettext_context
 * @param string $domain
 *
 * @return string
 * @throws Auryn\InjectionException Auryn Exception.
 *
 */
function icl_sw_filters_nxgettext( $translation, $single, $plural, $number, $_gettext_context, $domain ) {
    return icl_sw_filters_ngettext( $translation, $single, $plural, $number, $domain, $_gettext_context );
}

/**
 * @return array Translated User IDs
 */
function icl_st_register_user_strings_all(){
	global $sitepress, $authordata;
	$wpml_translated_users = new WPML_ST_User_Fields( $sitepress, $authordata );
	return $wpml_translated_users->init_register_strings();
}

function icl_st_update_string_actions( $context, $name, $old_value, $new_value, $force_complete = false ) {
	if ( class_exists( 'WPML_WPDB_User' ) ) {
		global $wpdb;
		require_once dirname( __FILE__ ) . '/wpml-st-string-update.class.php';

		$string_update = new WPML_ST_String_Update( $wpdb );
		$string_update->update_string( $context, $name, $old_value, $new_value, $force_complete );
	}
}

function icl_st_update_widget_title_actions($old_options, $new_options){

    if(isset($new_options['title'])){ // case of 1 instance only widgets
        $buf = $new_options;
        unset($new_options);
        $new_options[0] = $buf;
        unset($buf);
        $buf = $old_options;
        unset($old_options);
        $old_options[0] = $buf;
        unset($buf);
    }

    foreach($new_options as $k=>$o){
        if(isset($o['title'])){
            if(isset($old_options[$k]['title']) && $old_options[$k]['title']){
                icl_st_update_string_actions( WP_Widget_Text_Icl::STRING_DOMAIN, 'widget title - ' . md5( $old_options[ $k ]['title'] ), $old_options[ $k ]['title'], $o['title'] );
            }else{
                if($new_options[$k]['title']){
                    icl_register_string( WP_Widget_Text_Icl::STRING_DOMAIN, 'widget title - ' . md5( $new_options[ $k ]['title'] ), $new_options[ $k ]['title'] );
                }
            }
        }
    }
}

function icl_st_update_text_widgets_actions($old_options, $new_options){
    global $wpdb;

    // remove filter for showing permalinks instead of sticky links while saving
    $GLOBALS['__disable_absolute_links_permalink_filter'] = 1;

    $widget_text = get_option('widget_text');
    if(is_array($widget_text)){
        foreach($widget_text as $k=>$w){
            if(isset($old_options[$k]['text']) && trim($old_options[$k]['text']) && $old_options[$k]['text'] != $w['text']){
                $old_md5 = md5($old_options[$k]['text']);
                $string = $wpdb->get_row($wpdb->prepare( "SELECT id, value, status FROM {$wpdb->prefix}icl_strings WHERE context=%s AND name=%s", WP_Widget_Text_Icl::STRING_DOMAIN, 'widget body - ' . $old_md5 ) );
                if ($string) {
                    icl_st_update_string_actions( WP_Widget_Text_Icl::STRING_DOMAIN, 'widget body - ' . $old_md5, $old_options[ $k ]['text'], $w['text'] );
                } else {
                    icl_register_string( WP_Widget_Text_Icl::STRING_DOMAIN, 'widget body - ' . md5($w['text']), $w['text']);
                }
            }elseif(isset($new_options[$k]['text']) && (!isset($old_options[$k]['text']) || $old_options[$k]['text']!=$new_options[$k]['text'])){
                icl_register_string( WP_Widget_Text_Icl::STRING_DOMAIN, 'widget body - ' . md5( $new_options[ $k ]['text'] ), $new_options[ $k ]['text'] );
            }
        }
    }

    // add back the filter for showing permalinks instead of sticky links after saving
    unset($GLOBALS['__disable_absolute_links_permalink_filter']);

}

function icl_st_get_contexts( $status ) {
    global $sitepress, $wpdb, $wp_query;

	$wpml_strings = new WPML_ST_Strings( $sitepress, $wpdb, $wp_query );
    return $wpml_strings->get_per_domain_counts( $status );
}

function icl_st_admin_notices(){
    global $icl_st_err_str;
    if($icl_st_err_str){
        echo '<div class="error"><p>' . $icl_st_err_str . '</p></div>';
    }
}

function icl_st_generate_po_file( $strings ) {

	require_once( WPML_ST_PATH . '/inc/gettext/wpml-po-parser.class.php' );

	$po = WPML_PO_Parser::create_po( $strings );

	return $po;
}

function _icl_st_get_options_writes($path){
    static $found_writes = array();
    if(is_dir($path)){
        $dh = opendir($path);
        while($file = readdir($dh)){
            if($file=="." || $file=="..") continue;
            if(is_dir($path . '/' . $file)){
                _icl_st_get_options_writes($path . '/' . $file);
            }elseif(preg_match('#(\.php|\.inc)$#i', $file)){
                $content = file_get_contents($path . '/' . $file);
                $int = preg_match_all('#(add|update)_option\(([^,]+),([^)]+)\)#im', $content, $matches);
                if($int){
                    foreach($matches[2] as $m){
                        $option_name = trim($m);
                        if(0 === strpos($option_name, '"') || 0 === strpos($option_name, "'")){
                            $option_name = trim($option_name, "\"'");
                        }elseif(false === strpos($option_name, '$')){
                            if(false !== strpos($option_name, '::')){
                                $cexp = explode('::', $option_name);
                                if (class_exists($cexp[0])){
                                    if (defined($cexp[0].'::'. $cexp[1])){
                                        $option_name = constant($cexp[0].'::'. $cexp[1]);
                                    }
                                }
                            }else{
                                if (defined( $option_name )){
                                    $option_name = constant($option_name);
                                }
                            }
                        }else{
                            $option_name = false;
                        }
                        if($option_name){
                            $found_writes[] = $option_name;
                        }
                    }
                }
            }
        }
    }
    return $found_writes;
}

if ( ! function_exists( 'array_unique_recursive' ) ) {
	function array_unique_recursive( $array ) {
		$scalars = array();
		foreach ( $array as $key => $value ) {
			if ( is_scalar( $value ) ) {
				if ( isset( $scalars[ $value ] ) ) {
					unset( $array[ $key ] );
				} else {
					$scalars[ $value ] = true;
				}
			} elseif ( is_array( $value ) ) {
				$array[ $key ] = array_unique_recursive( $value );
			}
		}

		return $array;
	}
}

function _icl_st_filter_empty_options_out($array){
    $empty_found = false;
    foreach($array as $k=>$v){
        if(is_array($v) && !empty($v)){
            list($array[$k], $empty_found) = _icl_st_filter_empty_options_out($v);
        }else{
            if(empty($v)){
                unset($array[$k]);
                $empty_found = true;
            }
        }
    }
    return array($array, $empty_found);
}

function wpml_register_admin_strings($serialized_array){
    try{
        wpml_st_load_admin_texts()->icl_register_admin_options(unserialize($serialized_array));
    }catch(Exception $e){
        trigger_error($e->getMessage(), E_USER_WARNING);
    }
}

function icl_is_string_translation($translation) {
    // determine if the $translation data is for string translation.

    foreach($translation as $key => $value) {
        if($key == 'body' or $key == 'title') {
            return false;
        }
        if (preg_match("/string-(.*)/", $key)){
            return true;
        }
    }

    // if we get here assume it's not a string.
    return false;

}

function icl_translation_add_string_translation( $rid, $translation, $lang_code ) {
	global $wpdb;
	foreach ( $translation as $key => $value ) {
		if ( preg_match( "/string-(.*)/", $key, $match ) ) {
			$string_id = $match[ 1 ];

            $string_translation_id = $wpdb->get_var($wpdb->prepare("SELECT id
                                                      FROM {$wpdb->prefix}icl_string_translations
                                                      WHERE string_id=%d AND language=%s",
                                                     $string_id, $lang_code ) );

			$md5_when_sent        = $wpdb->get_var( $wpdb->prepare( "	SELECT md5
																		FROM {$wpdb->prefix}icl_string_status
                														WHERE rid=%d AND string_translation_id=%d",
			                                                        $rid, $string_translation_id ) );
			$current_string_value = $wpdb->get_var( $wpdb->prepare( "	SELECT value
																		FROM {$wpdb->prefix}icl_strings
																		WHERE id=%d",
			                                                        $string_id ) );
			if ( $md5_when_sent == md5( $current_string_value ) ) {
				$status = ICL_TM_COMPLETE;
			} else {
				$status = ICL_TM_NEEDS_UPDATE;
			}
			$value = str_replace( '&#0A;', "\n", $value );
			icl_add_string_translation( $string_id, $lang_code, html_entity_decode( $value ), $status );
		}
	}

	return true;
}

function icl_st_get_pending_string_translations_stats() {
    global $wpdb, $sitepress, $wp_query;

    $strings      = new WPML_ST_Strings( $sitepress, $wpdb, $wp_query );
    $current_user = $sitepress->get_current_user();

    return $strings->get_pending_translation_stats( $current_user );
}

function icl_st_is_translator(){
    return current_user_can('translate')
	&& !current_user_can('manage_options')
	&& !current_user_can('manage_categories')
	&& !current_user_can('wpml_manage_string_translation');
}

function icl_st_admin_notices_string_updated() {
	?>
	<div class="updated">
			<p><?php _e( 'Strings translations updated', 'wpml-string-translation' ); ?></p>
	</div>
	<?php
}

/**
 * @param string $path
 *
 * @return bool
 */
function wpml_st_file_path_is_valid( $path ) {
	return (bool)( validate_file( $path ) === 0 || validate_file( $path ) === 2 );
}

/**
 * @param string|array $context
 *
 * @return array
 */
function wpml_st_extract_context_parameters( $context ) {
	if ( is_array( $context ) ) {
		$domain = isset ( $context[ 'domain' ] ) ? $context[ 'domain' ] : '';
		$gettext_context = isset ( $context[ 'context' ] ) ? $context[ 'context' ] : '';
	} else {
		$domain = $context;
		$gettext_context = '';
	}

	return array($domain, $gettext_context);
}
