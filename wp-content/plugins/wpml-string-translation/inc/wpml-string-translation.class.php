<?php
/**
 * WPML_String_Translation class file.
 *
 * @package WPML\ST
 */

use WPML\ST\Gettext\AutoRegisterSettings;
use WPML\ST\StringsFilter\Translator;
use function WPML\Container\make;

/**
 * Class WPML_String_Translation
 */
class WPML_String_Translation {

	const CACHE_GROUP = 'wpml-string-translation';

	private $load_priority = 400;

	private $messages = array();

	private $string_filters = array();

	private $active_languages;

	private $current_string_language_cache = array();

	/** @var  WPML_ST_String_Factory $string_factory */
	private $string_factory;

	/**
	 * @var string
	 */
	private $admin_language;

	/**
	 * @var bool
	 */
	private $is_admin_action_from_referer;

	/** @var  SitePress $sitepress */
	protected $sitepress;

	/**
	 * @var WPML_WP_Cache
	 */
	private $cache;

	/**
	 * @param SitePress              $sitepress
	 * @param WPML_ST_String_Factory $string_factory
	 */
	public function __construct( SitePress $sitepress, WPML_ST_String_Factory $string_factory ) {
		$this->sitepress      = $sitepress;
		$this->string_factory = $string_factory;
	}

	/**
	 * Sets up basic actions hooked by ST
	 */
	public function set_basic_hooks() {
		if ( $this->sitepress->get_wp_api()->constant( 'WPML_TM_VERSION' ) ) {
			add_action( 'wpml_tm_loaded', array( $this, 'load' ) );
		} else {
			add_action(
				'wpml_loaded',
				array( $this, 'load' ),
				$this->load_priority
			);
		}
		add_action(
			'plugins_loaded',
			array( $this, 'check_db_for_gettext_context' ),
			1000
		);
		add_action(
			'wpml_language_has_switched',
			array( $this, 'wpml_language_has_switched' )
		);

	}

	/**
	 * Populates the internal cache for all language codes.
	 *
	 * @used-by WPML_String_Translation::get_string_filter to not load string filters
	 *                                                     for languages that do not
	 *                                                     exist.
	 * @used-by WPML_String_Translation::get_admin_string_filter See above.
	 */
	function init_active_languages() {
		$this->active_languages = array_keys( $this->sitepress->get_languages() );
	}

	function load() {
		global $sitepress, $wpdb;

		if ( ! $sitepress || ! $sitepress->get_setting( 'setup_complete' ) ) {
			return;
		}

		$this->plugin_localization();

		$factory = new WPML_ST_Upgrade_Command_Factory( $wpdb, $sitepress );
		$upgrade = new WPML_ST_Upgrade( $sitepress, $factory );
		$upgrade->run();

		$this->init_active_languages();

		$wpml_string_shortcode = new WPML\ST\Shortcode( $wpdb );
		$wpml_string_shortcode->init_hooks();

		wpml_st_load_admin_texts();

		add_action( 'init', array( $this, 'init' ) );

		$action_filter_loader = new WPML_Action_Filter_Loader();
		$action_filter_loader->load(
			array(
				'WPML_Slug_Translation_Factory',
			)
		);

		add_filter( 'pre_update_option_blogname', array( $this, 'pre_update_option_blogname' ), 5, 2 );
		add_filter( 'pre_update_option_blogdescription', array( $this, 'pre_update_option_blogdescription' ), 5, 2 );

		// Handle Admin Notices

		add_action( 'icl_ajx_custom_call', array( $this, 'ajax_calls' ), 10, 2 );

		/**
		 * @deprecated 3.3 - Each string has its own language now.
		 */
		add_filter( 'WPML_ST_strings_language', array( $this, 'get_strings_language' ) );
		add_filter( 'wpml_st_strings_language', array( $this, 'get_strings_language' ) );

		add_action( 'wpml_st_delete_all_string_data', array( $this, 'delete_all_string_data' ), 10, 1 );

		add_filter( 'wpml_st_string_status', array( $this, 'get_string_status_filter' ), 10, 2 );
		add_filter( 'wpml_string_id', array( $this, 'get_string_id_filter' ), 10, 2 );
		add_filter( 'wpml_get_string_language', array( $this, 'get_string_language_filter' ), 10, 3 );

		do_action( 'wpml_st_loaded' );
	}

	function init() {

		global $wpdb, $sitepress;

		if ( is_admin() ) {
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'thickbox' );

			$reset = new WPML_ST_Reset( $wpdb );
			add_action( 'wpml_reset_plugins_after', array( $reset, 'reset' ) );
		}

		add_action( 'wpml_admin_menu_configure', array( $this, 'menu' ) );

		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );

		$current_page = array_key_exists( 'page', $_GET ) ? $_GET['page'] : '';
		if ( $current_page && is_admin() ) {

			$allowed_pages_for_resources = array( WPML_ST_FOLDER . '/menu/string-translation.php' );
			if ( in_array( $current_page, $allowed_pages_for_resources, true ) && current_user_can( 'manage_options' ) && empty( $_POST ) ) {
				wp_enqueue_script( 'wpml-st-change-lang', WPML_ST_URL . '/res/js/change_string_lang.js', array( 'jquery', 'jquery-ui-dialog', 'wpml-st-scripts' ), WPML_ST_VERSION );
			}

			$allowed_pages_for_resources[] = ICL_PLUGIN_FOLDER . '/menu/theme-localization.php';
			if ( in_array( $current_page, $allowed_pages_for_resources, true ) ) {
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wpml-st-settings', WPML_ST_URL . '/res/js/settings.js', array( 'jquery' ), WPML_ST_VERSION );
				wp_enqueue_script( 'wpml-st-scripts', WPML_ST_URL . '/res/js/scripts.js', array( 'jquery', 'jquery-ui-dialog' ), WPML_ST_VERSION );
				wp_enqueue_script( OTGS_Assets_Handles::POPOVER_TOOLTIP );
				wp_enqueue_style( OTGS_Assets_Handles::POPOVER_TOOLTIP );
				wp_enqueue_script( 'wpml-auto-register-strings', WPML_ST_URL . '/res/js/auto-register-strings.js', array( 'jquery', 'jquery-ui-dialog', 'wpml-st-scripts' ), WPML_ST_VERSION );
				wp_enqueue_script( 'wpml-st-change-domian-lang', WPML_ST_URL . '/res/js/change_string_domain_lang.js', array( 'jquery', 'jquery-ui-dialog' ), WPML_ST_VERSION );
				wp_enqueue_script( 'wpml-st-translation_basket', WPML_ST_URL . '/res/js/wpml_string_translation_basket.js', array( 'jquery' ), WPML_ST_VERSION );
				wp_enqueue_script( 'wpml-plugin-list-table-filter', WPML_ST_URL . '/res/js/wpml-plugin-list-table-filter.js', array( 'jquery' ), WPML_ST_VERSION );
				wp_enqueue_style( 'wpml-st-styles', WPML_ST_URL . '/res/css/style.css', array(), WPML_ST_VERSION );
				wp_enqueue_style( 'wpml-dialog', ICL_PLUGIN_URL . '/res/css/dialog.css', array( 'otgs-dialogs' ), ICL_SITEPRESS_VERSION );
				wp_enqueue_style( 'wp-jquery-ui-dialog' );
			}
		}

		add_action( 'wpml_custom_localization_type', array( $this, 'localization_type_ui' ) );
		add_action( 'wp_ajax_st_theme_localization_rescan', array( $this, 'scan_theme_for_strings' ) );
		add_action( 'wp_ajax_st_plugin_localization_rescan', array( $this, 'scan_plugins_for_strings' ) );
		add_action( 'wp_ajax_icl_st_pop_download', array( $this, 'plugin_po_file_download' ) );
		add_action( 'wp_ajax_wpml_change_string_lang', array( $this, 'change_string_lang_ajax_callback' ) );
		add_action( 'wp_ajax_wpml_change_string_lang_of_domain', array( $this, 'change_string_lang_of_domain_ajax_callback' ) );

		// auto-registration settings: saving excluded contexts
		/** @var AutoRegisterSettings $auto_register_settings */
		$auto_register_settings = WPML\Container\make( AutoRegisterSettings::class );
		add_action( 'wp_ajax_wpml_st_exclude_contexts', array( $auto_register_settings, 'saveExcludedContexts' ) );

		return true;
	}

	function plugin_localization() {
		load_plugin_textdomain( 'wpml-string-translation', false, WPML_ST_FOLDER . '/locale' );
	}

	/**
	 * @param string       $context
	 * @param string       $name
	 * @param string|false $original_value
	 * @param boolean|null $has_translation
	 * @param null|string  $target_lang
	 *
	 * @return string|bool
	 * @since 2.2.3
	 *
	 */
	function translate_string( $context, $name, $original_value = false, &$has_translation = null, $target_lang = null ) {

		return icl_translate( $context, $name, $original_value, false, $has_translation, $target_lang );
	}

	function add_message( $text, $type = 'updated' ) {
		$this->messages[] = array(
			'type' => $type,
			'text' => $text,
		);
	}

	function show_messages() {
		if ( ! empty( $this->messages ) ) {
			foreach ( $this->messages as $m ) {
				printf( '<div class="%s fade"><p>%s</p></div>', $m['type'], $m['text'] );
			}
		}
	}

	function ajax_calls( $call, $data ) {
		require_once WPML_ST_PATH . '/inc/admin-texts/wpml-admin-text-configuration.php';

		switch ( $call ) {

			case 'icl_st_delete_strings':
				$arr = explode( ',', $data['value'] );
				wpml_unregister_string_multi( $arr );
				echo '1';
				break;
		}
	}

	/**
	 * @param string $menu_id
	 */
	function menu( $menu_id ) {
		if ( 'WPML' !== $menu_id ) {
			return;
		}

		if ( ! $this->sitepress || ! $this->sitepress->get_wp_api()->constant( 'ICL_PLUGIN_PATH' ) ) {
			return;
		}

		$setup_complete = apply_filters( 'wpml_get_setting', false, 'setup_complete' );
		if ( ! $setup_complete ) {
			return;
		}

		global $wpdb;
		$existing_content_language_verified = apply_filters(
			'wpml_get_setting',
			false,
			'existing_content_language_verified'
		);

		if ( ! $existing_content_language_verified ) {
			return;
		}

		if ( current_user_can( 'wpml_manage_string_translation' ) || current_user_can( 'manage_translations' ) ) {
			$menu               = array();
			$menu['order']      = 800;
			$menu['page_title'] = __( 'String Translation', 'wpml-string-translation' );
			$menu['menu_title'] = __( 'String Translation', 'wpml-string-translation' );
			$menu['capability'] = current_user_can( 'wpml_manage_string_translation' ) ? 'wpml_manage_string_translation' : 'manage_translations';
			$menu['menu_slug']  = WPML_ST_FOLDER . '/menu/string-translation.php';

			do_action( 'wpml_admin_menu_register_item', $menu );
		}
	}

	function plugin_action_links( $links, $file ) {
		 $this_plugin = basename( WPML_ST_PATH ) . '/plugin.php';
		if ( $file == $this_plugin ) {
			$links[] = '<a href="admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php">' .
				__( 'Configure', 'wpml-string-translation' ) . '</a>';
		}

		return $links;
	}

	public function localization_type_ui() {
		$plugin_localization_factory = new WPML_ST_Plugin_Localization_UI_Factory();
		$plugin_localization         = $plugin_localization_factory->create();

		$theme_localization_factory = new WPML_ST_Theme_Localization_UI_Factory();
		$theme_localization         = $theme_localization_factory->create();

		$localization = new WPML_Theme_Plugin_Localization_UI();

		echo $localization->render( $theme_localization );
		echo $localization->render( $plugin_localization );
	}

	function scan_theme_for_strings() {
		require_once WPML_ST_PATH . '/inc/gettext/wpml-theme-string-scanner.class.php';

		$file_hashing     = new WPML_ST_File_Hashing();
		$scan_for_strings = new WPML_Theme_String_Scanner( wpml_get_filesystem_direct(), $file_hashing );
		$scan_for_strings->scan();
	}

	function scan_plugins_for_strings() {
		require_once WPML_ST_PATH . '/inc/gettext/wpml-plugin-string-scanner.class.php';
		$file_hashing     = new WPML_ST_File_Hashing();
		$scan_for_strings = new WPML_Plugin_String_Scanner( wpml_get_filesystem_direct(), $file_hashing );
		$scan_for_strings->scan();
	}

	function plugin_po_file_download( $file = false, $recursion = 0 ) {
		 global $__wpml_st_po_file_content;

		if ( empty( $file ) && ! empty( $_GET['file'] ) ) {
			$file = WPML_PLUGINS_DIR . '/' . filter_var( $_GET['file'], FILTER_SANITIZE_STRING );
		}
		if ( empty( $file ) && ! wpml_st_file_path_is_valid( $file ) ) {
			return;
		}

		if ( is_null( $__wpml_st_po_file_content ) ) {
			$__wpml_st_po_file_content = '';
		}

		require_once WPML_ST_PATH . '/inc/potx.php';
		require_once WPML_ST_PATH . '/inc/potx-callback.php';

		if ( is_file( $file ) && WPML_PLUGINS_DIR == dirname( $file ) ) {

			_potx_process_file( $file, 0, 'wpml_st_pos_scan_store_results', '_potx_save_version', '' );
		} else {

			if ( ! $recursion ) {
				$file = dirname( $file );
			}

			if ( is_dir( $file ) ) {
				$dh = opendir( $file );
				while ( $dh && false !== ( $f = readdir( $dh ) ) ) {
					if ( 0 === strpos( $f, '.' ) ) {
						continue;
					}
					$this->plugin_po_file_download( $file . '/' . $f, $recursion + 1 );
				}
			} elseif ( preg_match( '#(\.php|\.inc)$#i', $file ) ) {
				_potx_process_file( $file, 0, 'wpml_st_pos_scan_store_results', '_potx_save_version', '' );
			}
		}

		if ( ! $recursion ) {
			$po  = WPML_PO_Parser::get_po_file_header();
			$po .= $__wpml_st_po_file_content;

			$filename = isset( $_GET['domain'] ) ?
				filter_var( $_GET['domain'], FILTER_SANITIZE_STRING ) :
				basename( $file );

			header( 'Content-Type: application/force-download' );
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Type: application/download' );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '.po"' );
			header( 'Content-Length: ' . strlen( $po ) );
			echo $po;
			exit( 0 );
		}
	}

	/**
	 * @param string $string value of a string
	 * @param string $lang_code language code of the string
	 *
	 * @return int number of words in the string
	 */
	public function estimate_word_count( $string, $lang_code ) {
		$string = strip_tags( $string );

		return in_array(
			$lang_code,
			array(
				'ja',
				'ko',
				'zh-hans',
				'zh-hant',
				'mn',
				'ne',
				'hi',
				'pa',
				'ta',
				'th',
			)
		) ? strlen( $string ) / 6
			: count( explode( ' ', $string ) );
	}

	function cancel_remote_translation( $rid ) {
		/** @var wpdb $wpdb */
		global $wpdb;

		$translation_ids = $wpdb->get_col(
			$wpdb->prepare(
				"	SELECT string_translation_id
															FROM {$wpdb->prefix}icl_string_status
															WHERE rid = %d",
				$rid
			)
		);
		$cancel_count    = 0;
		foreach ( $translation_ids as $translation_id ) {
			$res          = (bool) $this->cancel_local_translation( $translation_id );
			$cancel_count = $res ? $cancel_count + 1 : $cancel_count;
		}

		return $cancel_count;
	}

	function cancel_local_translation( $id, $return_original_id = false ) {
		global $wpdb;
		$string_id = $wpdb->get_var(
			$wpdb->prepare(
				"	SELECT string_id
														FROM {$wpdb->prefix}icl_string_translations
														WHERE id=%d AND status IN (%d, %d)",
				$id,
				ICL_TM_IN_PROGRESS,
				ICL_TM_WAITING_FOR_TRANSLATOR
			)
		);
		if ( $string_id ) {
			$wpdb->update(
				$wpdb->prefix . 'icl_string_translations',
				array(
					'status'              => ICL_TM_NOT_TRANSLATED,
					'translation_service' => null,
					'translator_id'       => null,
					'batch_id'            => null,
				),
				array( 'id' => $id )
			);
			icl_update_string_status( $string_id );
			$res = $return_original_id ? $string_id : $id;
		} else {
			$res = false;
		}

		return $res;
	}

	/**
	 * @param string $value
	 * @param string $old_value
	 *
	 * @return array|string
	 */
	function pre_update_option_blogname( $value, $old_value ) {
		return $this->pre_update_option_settings(
			WPML_ST_Blog_Name_And_Description_Hooks::STRING_NAME_BLOGNAME,
			$value,
			$old_value
		);
	}

	/**
	 * @param string $value
	 * @param string $old_value
	 *
	 * @return array|string
	 */
	function pre_update_option_blogdescription( $value, $old_value ) {
		return $this->pre_update_option_settings(
			WPML_ST_Blog_Name_And_Description_Hooks::STRING_NAME_BLOGDESCRIPTION,
			$value,
			$old_value
		);
	}

	/**
	 * @param string       $option name of the option
	 * @param string|array $value new value of the option
	 * @param string|array $old_value currently saved value for the option
	 *
	 * @return string|array the value actually to be written into the wp_options table
	 */
	function pre_update_option_settings( $option, $value, $old_value ) {
		$wp_api = $this->sitepress->get_wp_api();
		if ( $wp_api->is_multisite()
			 && $wp_api->ms_is_switched()
			 && ! $this->sitepress->get_setting( 'setup_complete' )
		) {
			return $value;
		}

		$option = new WPML_ST_Admin_Blog_Option(
			$this->sitepress,
			$this,
			$option
		);

		return $option->pre_update_filter( $old_value, $value );
	}

	/**
	 * Instantiates a new admin option translation object
	 *
	 * @param string $option_name
	 * @param string $language_code
	 *
	 * @return WPML_ST_Admin_Option_Translation
	 */
	public function get_admin_option( $option_name, $language_code = '' ) {

		return new WPML_ST_Admin_Option_Translation(
			$this->sitepress,
			$this,
			$option_name,
			$language_code
		);
	}

	/**
	 * @return WPML_ST_String_Factory
	 */
	public function string_factory() {

		return $this->string_factory;
	}

	/**
	 * @param string $lang_code
	 */
	public function clear_string_filter( $lang_code ) {
		unset( $this->string_filters[ $lang_code ] );
	}

	/**
	 * @param string $lang
	 *
	 * @return WPML_Displayed_String_Filter
	 */
	public function get_string_filter( $lang ) {

		if ( true === (bool) $this->active_languages && in_array( $lang, $this->active_languages, true ) ) {
			return $this->get_admin_string_filter( $lang );
		} else {
			return null;
		}
	}

	/**
	 * @param string $lang
	 *
	 * @return mixed|\WPML_Register_String_Filter|null
	 * @throws \WPML\Auryn\InjectionException
	 */
	public function get_admin_string_filter( $lang ) {
		global $sitepress_settings, $wpdb, $sitepress;

		if ( isset( $sitepress_settings['st']['db_ok_for_gettext_context'] ) ) {
			if ( ! ( isset( $this->string_filters[ $lang ] )
			         && 'WPML_Register_String_Filter' == get_class( $this->string_filters[ $lang ] ) )
			) {
				$this->string_filters[ $lang ] = isset( $this->string_filters[ $lang ] ) ? $this->string_filters[ $lang ] : false;

				/** @var AutoRegisterSettings $auto_register_settings */
				$auto_register_settings = WPML\Container\make( AutoRegisterSettings::class );

				$this->string_filters[ $lang ] = new WPML_Register_String_Filter(
					$wpdb,
					$sitepress,
					$this->string_factory,
					make( Translator::class, [ ':language' => $lang ] ),
					$auto_register_settings->getExcludedDomains()
				);
			}

			return $this->string_filters[ $lang ];
		} else {
			return null;
		}
	}

	/**
	 * @deprecated 3.3 - Each string has its own language now.
	 */
	public function get_strings_language( $language = '' ) {
		$string_settings = $this->get_strings_settings();

		$string_language = $language ? $language : 'en';
		if ( isset( $string_settings['strings_language'] ) ) {
			$string_language = $string_settings['strings_language'];
		}

		return $string_language;
	}

	public function delete_all_string_data( $string_id ) {
		global $wpdb;

		$icl_string_positions_query    = "DELETE FROM {$wpdb->prefix}icl_string_positions WHERE string_id=%d";
		$icl_string_status_query       = "DELETE FROM {$wpdb->prefix}icl_string_status WHERE string_translation_id IN (SELECT id FROM {$wpdb->prefix}icl_string_translations WHERE string_id=%d)";
		$icl_string_translations_query = "DELETE FROM {$wpdb->prefix}icl_string_translations WHERE string_id=%d";
		$icl_strings_query             = "DELETE FROM {$wpdb->prefix}icl_strings WHERE id=%d";

		$icl_string_positions_prepare    = $wpdb->prepare( $icl_string_positions_query, $string_id );
		$icl_string_status_prepare       = $wpdb->prepare( $icl_string_status_query, $string_id );
		$icl_string_translations_prepare = $wpdb->prepare( $icl_string_translations_query, $string_id );
		$icl_strings_prepare             = $wpdb->prepare( $icl_strings_query, $string_id );

		$wpdb->query( $icl_string_positions_prepare );
		$wpdb->query( $icl_string_status_prepare );
		$wpdb->query( $icl_string_translations_prepare );
		$wpdb->query( $icl_strings_prepare );

	}

	public function get_strings_settings() {
		global $sitepress;

		if ( version_compare( ICL_SITEPRESS_VERSION, '3.2', '<' ) ) {
			global $sitepress_settings;

			$string_settings = isset( $sitepress_settings['st'] ) ? $sitepress_settings['st'] : array();
		} else {
			$string_settings = $sitepress ? $sitepress->get_string_translation_settings() : array();
		}

		$string_settings['strings_language'] = 'en';
		if ( ! isset( $string_settings['icl_st_auto_reg'] ) ) {
			$string_settings['icl_st_auto_reg'] = 'disable';
		}
		$string_settings['strings_per_page'] = ICL_STRING_TRANSLATION_AUTO_REGISTER_THRESHOLD;

		return $string_settings;
	}

	/**
	 * @param null $empty   Not used, but needed for the hooked filter
	 * @param int  $string_id
	 *
	 * @return null|string
	 */
	public function get_string_status_filter( $empty = null, $string_id = 0 ) {
		return $this->get_string_status( $string_id );
	}

	/**
	 * @param int|null $default     Set the default value to return in case no string or more than one string is found
	 * @param array    $string_data {
	 *
	 * @type string    $context
	 * @type string    $name        Optional
	 *                           }
	 * @return int|null If there is more than one string_id, it will return the value set in $default.
	 */
	public function get_string_id_filter( $default = null, $string_data = array() ) {
		$result = $default;

		$string_id = $this->get_string_id( $string_data );

		return $string_id ? $string_id : $result;
	}

	private function get_string_status( $string_id ) {
		global $wpdb;
		$status = $wpdb->get_var(
			$wpdb->prepare(
				"
		            SELECT	MIN(status)
		            FROM {$wpdb->prefix}icl_string_translations
		            WHERE
		                string_id=%d
		            ",
				$string_id
			)
		);

		return $status !== null ? (int) $status : null;
	}

	/**
	 * @param array $string_data {
	 *
	 * @type string $context
	 * @type string $name        Optional
	 *                           }
	 * @return int|null
	 */
	private function get_string_id( $string_data ) {
		$context = isset( $string_data['context'] ) ? $string_data['context'] : null;
		$name    = isset( $string_data['name'] ) ? $string_data['name'] : null;

		$result = null;
		if ( $name && $context ) {
			global $wpdb;
			$string_id_query = "SELECT id FROM {$wpdb->prefix}icl_strings WHERE context=%s";
			$string_id_args  = array( $context );
			if ( $name ) {
				$string_id_query .= ' AND name=%s';
				$string_id_args[] = $name;
			}
			$string_id_prepare = $wpdb->prepare( $string_id_query, $string_id_args );
			$string_id         = $wpdb->get_var( $string_id_prepare );

			$result = (int) $string_id;
		}

		return $result;
	}

	/**
	 * @param null   $empty   Not used, but needed for the hooked filter
	 * @param string $domain
	 * @param string $name
	 *
	 * @return null|string
	 */
	public function get_string_language_filter( $empty = null, $domain = '', $name = '' ) {
		global $wpdb;

		$key                         = md5( $domain . '_' . $name );
		list( $string_lang, $found ) = $this->get_cache()->get_with_found( $key );

		if ( ! $found ) {
			$string_query   = "SELECT language FROM {$wpdb->prefix}icl_strings WHERE context=%s AND name=%s";
			$string_prepare = $wpdb->prepare( $string_query, $domain, $name );
			$string_lang    = $wpdb->get_var( $string_prepare );

			$this->get_cache()->set( $key, $string_lang, 600 );
		}

		return $string_lang;
	}

	/**
	 * @param WPML_WP_Cache $cache
	 */
	public function set_cache( WPML_WP_Cache $cache ) {
		$this->cache = $cache;
	}

	/**
	 * @return WPML_WP_Cache
	 */
	public function get_cache() {
		if ( null === $this->cache ) {
			$this->cache = new WPML_WP_Cache( self::CACHE_GROUP );
		}

		return $this->cache;
	}

	function check_db_for_gettext_context() {
		$string_settings = apply_filters( 'wpml_get_setting', false, 'st' );
		if ( ! isset( $string_settings['db_ok_for_gettext_context'] ) ) {

			if ( function_exists( 'icl_table_column_exists' ) && icl_table_column_exists( 'icl_strings', 'domain_name_context_md5' ) ) {
				$string_settings['db_ok_for_gettext_context'] = true;
				do_action( 'wpml_set_setting', 'st', $string_settings, true );
			}
		}
	}

	public function initialize_wp_and_widget_strings() {
		$this->check_db_for_gettext_context();

		icl_register_string(
			WPML_ST_Blog_Name_And_Description_Hooks::STRING_DOMAIN,
			WPML_ST_Blog_Name_And_Description_Hooks::STRING_NAME_BLOGNAME,
			get_option( 'blogname' )
		);

		icl_register_string(
			WPML_ST_Blog_Name_And_Description_Hooks::STRING_DOMAIN,
			WPML_ST_Blog_Name_And_Description_Hooks::STRING_NAME_BLOGDESCRIPTION,
			get_option( 'blogdescription' )
		);

		wpml_st_init_register_widget_titles();

		// create a list of active widgets
		$active_text_widgets = array();
		$widgets             = (array) get_option( 'sidebars_widgets' );
		foreach ( $widgets as $k => $w ) {
			if ( 'wp_inactive_widgets' != $k && $k != 'array_version' ) {
				if ( is_array( $widgets[ $k ] ) ) {
					foreach ( $widgets[ $k ] as $v ) {
						if ( preg_match( '#text-([0-9]+)#i', $v, $matches ) ) {
							$active_text_widgets[] = $matches[1];
						}
					}
				}
			}
		}

		$widget_text = get_option( 'widget_text' );
		if ( is_array( $widget_text ) ) {
			foreach ( $widget_text as $k => $w ) {
				if ( ! empty( $w ) && isset( $w['title'], $w['text'] ) && in_array( $k, $active_text_widgets ) && $w['text'] ) {
					icl_register_string( WPML_ST_WIDGET_STRING_DOMAIN, 'widget body - ' . md5( $w['text'] ), $w['text'] );
				}
			}
		}
	}

	/**
	 * Returns the language the current string is to be translated into.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function get_current_string_language( $name ) {
		if ( isset( $this->current_string_language_cache[ $name ] ) ) {
			return $this->current_string_language_cache[ $name ];
		}

		$key              = 'current_language';
		$found            = false;
		$current_language = WPML_Non_Persistent_Cache::get( $key, 'WPML_String_Translation', $found );
		if ( ! $found ) {
			$wp_api           = $this->sitepress->get_wp_api();
			$current_language = $wp_api->constant( 'DOING_AJAX' )
								&& $this->is_admin_action_from_referer()
				? $this->sitepress->user_lang_by_authcookie()
				: $this->sitepress->get_current_language();
			WPML_Non_Persistent_Cache::set( $key, $current_language, 'WPML_String_Translation' );
		}

		if ( $this->should_use_admin_language()
			 && ! WPML_ST_Blog_Name_And_Description_Hooks::is_string( $name ) ) {
			$admin_display_lang = $this->get_admin_language();
			$current_language   = $admin_display_lang ? $admin_display_lang : $current_language;
		}

		$ret = apply_filters(
			'icl_current_string_language',
			$current_language,
			$name
		);
		$this->current_string_language_cache[ $name ] = $ret === 'all'
			? $this->sitepress->get_default_language() : $ret;

		return $this->current_string_language_cache[ $name ];
	}

	public function should_use_admin_language() {
		$key                       = 'should_use_admin_language';
		$found                     = false;
		$should_use_admin_language = WPML_Non_Persistent_Cache::get( $key, 'WPML_String_Translation', $found );
		if ( ! $found ) {
			$wp_api                    = $this->sitepress->get_wp_api();
			$should_use_admin_language = $wp_api->constant( 'WP_ADMIN' ) && ( $this->is_admin_action_from_referer() || ! $wp_api->constant( 'DOING_AJAX' ) );
			WPML_Non_Persistent_Cache::set( $key, $should_use_admin_language, 'WPML_String_Translation' );
		}

		return $should_use_admin_language;
	}

	/**
	 * @return string
	 */
	public function get_admin_language() {
		if ( $this->sitepress->is_wpml_switch_language_triggered() ) {
			return $this->sitepress->get_admin_language();
		}

		if ( ! $this->admin_language ) {
			$this->admin_language = $this->sitepress->get_admin_language();
		}

		return $this->admin_language;
	}

	/**
	 * @return bool
	 */
	private function is_admin_action_from_referer() {
		if ( $this->is_admin_action_from_referer === null ) {
			$this->is_admin_action_from_referer = $this->sitepress->check_if_admin_action_from_referer();
		}

		return $this->is_admin_action_from_referer;
	}

	public function wpml_language_has_switched() {
		// clear the current language cache
		$this->current_string_language_cache = array();
	}

	public function change_string_lang_ajax_callback() {
		if ( ! $this->verify_ajax_call( 'wpml_change_string_language_nonce' ) ) {
			die( 'verification failed' );
		}

		global $wpdb;
		$change_string_language_dialog = new WPML_Change_String_Language_Select( $wpdb, $this->sitepress );

		$string_ids = array_map( 'intval', $_POST['strings'] );
		$lang       = filter_var( isset( $_POST['language'] ) ? $_POST['language'] : '', FILTER_SANITIZE_SPECIAL_CHARS );
		$response   = $change_string_language_dialog->change_language_of_strings( $string_ids, $lang );

		wp_send_json( $response );
	}

	public function change_string_lang_of_domain_ajax_callback() {
		if ( ! $this->verify_ajax_call( 'wpml_change_string_domain_language_nonce' ) ) {
			die( 'verification failed' );
		}

		global $wpdb, $sitepress;

		$change_string_language_domain_dialog = new WPML_Change_String_Domain_Language_Dialog( $wpdb, $sitepress, $this->string_factory );
		$response                             = $change_string_language_domain_dialog->change_language_of_strings(
			$_POST['domain'],
			isset( $_POST['langs'] ) ? $_POST['langs'] : array(),
			$_POST['language'],
			$_POST['use_default'] == 'true'
		);

		wp_send_json( $response );
	}

	private function verify_ajax_call( $ajax_action ) {
		return isset( $_POST['wpnonce'] ) && wp_verify_nonce( $_POST['wpnonce'], $ajax_action );
	}

}
