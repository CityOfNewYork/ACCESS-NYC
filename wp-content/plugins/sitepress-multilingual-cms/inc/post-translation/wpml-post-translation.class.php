<?php

/**
 * Class WPML_Post_Translation
 *
 * @package    wpml-core
 * @subpackage post-translation
 */
abstract class WPML_Post_Translation extends WPML_Element_Translation {

	protected $settings;
	protected $post_translation_sync;
	public static $defer_term_counting = false;

	/**
	 * @var WPML_Debug_BackTrace
	 */
	private $debug_backtrace;

	/**
	 * @param array $settings
	 * @param wpdb  $wpdb
	 */
	public function __construct( &$settings, &$wpdb ) {
		parent::__construct( $wpdb );
		$this->settings = $settings;
	}

	protected function is_setup_complete( ) {
		return isset( $this->settings[ 'setup_complete' ]) && $this->settings[ 'setup_complete' ];
	}

	public function init() {
		if ( $this->is_setup_complete() ) {
			add_action( 'save_post', array( $this, 'save_post_actions' ), 100, 2 );
			add_action( 'shutdown', array( $this, 'shutdown_action' ), PHP_INT_MAX );
		}
	}

	public function get_original_post_status( $trid, $source_lang_code = null ) {

		return $this->get_original_post_attr ( $trid, 'post_status', $source_lang_code );
	}

	public function get_original_post_ID( $trid, $source_lang_code = null ) {

		return $this->get_original_post_attr ( $trid, 'ID', $source_lang_code );
	}

	public function get_original_menu_order
	( $trid, $source_lang_code = null ) {

		return $this->get_original_post_attr ( $trid, 'menu_order', $source_lang_code );
	}

	public function get_original_comment_status( $trid, $source_lang_code = null ) {

		return $this->get_original_post_attr ( $trid, 'comment_status', $source_lang_code );
	}

	public function get_original_ping_status( $trid, $source_lang_code = null ) {

		return $this->get_original_post_attr ( $trid, 'ping_status', $source_lang_code );
	}

	public function get_original_post_format( $trid, $source_lang_code = null ) {

		return get_post_format ( $this->get_original_post_ID ( $trid, $source_lang_code ) );
	}

	/**
	 * @param int     $pidd
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public abstract function save_post_actions( $pidd, $post );

	public function shutdown_action() {
		if ( self::$defer_term_counting ) {
			self::$defer_term_counting = false;
			wp_defer_term_counting( false );
		}
	}

	public function trash_translation ( $trans_id ) {
		if ( !WPML_WordPress_Actions::is_bulk_trash( $trans_id ) ) {
			wp_trash_post( $trans_id );
		}
	}

	public function untrash_translation( $trans_id ) {
		if ( ! WPML_WordPress_Actions::is_bulk_untrash( $trans_id ) ) {
			wp_untrash_post( $trans_id );
		}
	}

	function untrashed_post_actions( $post_id ) {
		$translation_sync = $this->get_sync_helper ();
		$translation_sync->untrashed_post_actions ( $post_id );
	}

	public function delete_post_translation_entry( $post_id ) {

		$update_args = array( 'context' => 'post', 'element_id' => $post_id );
		do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'before_delete' ) ) );

		$sql = $this->wpdb->prepare( "DELETE FROM {$this->wpdb->prefix}icl_translations
								WHERE element_id = %d
									AND element_type LIKE 'post%%'
								LIMIT 1",
		                       $post_id );
		$res = $this->wpdb->query( $sql );

		do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'after_delete' ) ) );

		return $res;
	}

	public function trashed_post_actions( $post_id ) {
		$this->delete_post_actions( $post_id, true );
	}

	/**
	 * This function holds all actions to be run after deleting a post.
	 * 1. Delete the posts entry in icl_translations.
	 * 2. Set one of the posts translations or delete all translations of the post, depending on sitepress settings.
	 *
	 * @param Integer $post_id
	 * @param bool $keep_db_entries Sets whether icl_translations entries are to be deleted or kept, when hooking this to
	 * post trashing we want them to be kept.
	 */
	public function delete_post_actions( $post_id, $keep_db_entries = false ) {
		$translation_sync = $this->get_sync_helper ();
		$translation_sync->delete_post_actions ( $post_id, $keep_db_entries );
	}

	/**
	 * @param int    $post_id
	 * @param string $post_status
	 *
	 * @return null|int
	 */
	abstract function get_save_post_trid( $post_id, $post_status );

	/**
	 * @param integer $post_id
	 * @param SitePress $sitepress
	 * @return bool|mixed|null|string|void
	 */
	public function get_save_post_lang( $post_id, $sitepress ) {
		$language_code = $this->get_element_lang_code ( $post_id );
		$language_code = $language_code ? $language_code : $sitepress->get_current_language ();
		$language_code = $sitepress->is_active_language ( $language_code ) ? $language_code
			: $sitepress->get_default_language ();

		return apply_filters ( 'wpml_save_post_lang', $language_code );
	}

	/**
	 * @param int    $trid
	 * @param string $language_code
	 * @param string $default_language
	 *
	 * @return string|null
	 */
	protected abstract function get_save_post_source_lang( $trid, $language_code, $default_language );

	/**
	 * Sets a posts language details, invalidates caches relating to the post and triggers
	 * synchronisation actions across translations of the just saved post.
	 *
	 * @param int     $trid
	 * @param array   $post_vars
	 * @param string  $language_code
	 * @param string  $source_language
	 *
	 * @used-by \WPML_Post_Translation::save_post_actions as final step of the WPML Core save_post actions
	 */
	protected function after_save_post( $trid, $post_vars, $language_code, $source_language ) {
		$this->maybe_set_elid( $trid, $post_vars['post_type'], $language_code, $post_vars['ID'], $source_language );
		$translation_sync = $this->get_sync_helper();
		$original_id      = $this->get_original_element( $post_vars['ID'] );
		$translation_sync->sync_with_translations( $original_id ? $original_id : $post_vars['ID'], $post_vars );
		$translation_sync->sync_with_duplicates( $post_vars['ID'] );
		if ( ! function_exists( 'icl_cache_clear' ) ) {
			require_once WPML_PLUGIN_PATH . '/inc/cache.php';
		}
		icl_cache_clear( $post_vars['post_type'] . 's_per_language', true );
		if ( ! in_array( $post_vars['post_type'], array( 'nav_menu_item', 'attachment' ), true ) ) {
			do_action( 'wpml_tm_save_post', $post_vars['ID'], get_post( $post_vars['ID'] ), false );
		}
		// Flush object cache.
		$this->flush_object_cache_for_groups( array( 'ls_languages', 'element_translations' ) );

		do_action( 'wpml_after_save_post', $post_vars['ID'], $trid, $language_code, $source_language );
	}

	/**
	 * Create new instance of WPML_WP_Cache for each group and flush cache for group.
	 * @param array $groups
	 */
	private function flush_object_cache_for_groups( $groups = array() ) {
		if ( ! empty( $groups ) ) {
			foreach ( $groups as $group ) {
				$cache            = new WPML_WP_Cache( $group );
				$cache->flush_group_cache();
			}
		}
	}

	private function get_original_post_attr( $trid, $attribute, $source_lang_code ) {
		$legal_attributes = array(
			'post_status',
			'post_date',
			'menu_order',
			'comment_status',
			'ping_status',
			'ID'
		);
		$res              = false;
		if ( in_array ( $attribute, $legal_attributes, true ) ) {
			$attribute      = 'p.' . $attribute;
			$source_snippet = $source_lang_code === null
				? " AND wpml_translations.source_language_code IS NULL "
				: $this->wpdb->prepare ( " AND wpml_translations.language_code = %s ", $source_lang_code );
			$res            = $this->wpdb->get_var (
				$this->wpdb->prepare (
					"SELECT {$attribute}
					 " . $this->get_element_join() . "
					 WHERE wpml_translations.trid=%d
					{$source_snippet}
					LIMIT 1",
					$trid
				)
			);
		}

		return $res;
	}

	public function has_save_post_action( $post ) {
		if ( ! $post ) {
			return false;
		}
		$is_auto_draft              = isset( $post->post_status ) && $post->post_status === 'auto-draft';
		$is_editing_different_post  = $this->is_editing_different_post( $post->ID );
		$is_saving_a_revision       = array_key_exists( 'post_type', $_POST ) && 'revision' === $_POST['post_type'];
		$is_untrashing              = array_key_exists( 'action', $_GET ) && 'untrash' === $_GET['action'];
		$is_auto_save               = array_key_exists( 'autosave', $_POST );
		$skip_sitepress_actions     = array_key_exists( 'skip_sitepress_actions', $_POST );
		$is_post_a_revision         = 'revision' === $post->post_type;
		$is_scheduled_to_be_trashed = get_post_meta( $post->ID, '_wp_trash_meta_status', true );
		$is_add_meta_action         = isset( $_POST['action'] ) && 'add-meta' === $_POST['action'];
		$is_inner_post_insertion    = $this->is_inner_post_insertion();

		return $this->is_translated_type( $post->post_type )
		       && ! ( $is_auto_draft
		              || $is_auto_save
		              || $skip_sitepress_actions
		              || ( $is_editing_different_post && ! $is_inner_post_insertion )
		              || $is_saving_a_revision
		              || $is_post_a_revision
		              || $is_scheduled_to_be_trashed
		              || $is_add_meta_action
		              || $is_untrashing );
	}

	/**
	 * @param int $post_id
	 *
	 * @return bool
	 */
	protected function is_editing_different_post( $post_id ) {
		return array_key_exists( 'post_ID', $_POST ) && (int) $_POST['post_ID'] && $post_id != $_POST['post_ID'];
	}

	protected function get_element_join() {

		return "FROM {$this->wpdb->prefix}icl_translations wpml_translations
				JOIN {$this->wpdb->posts} p
					ON wpml_translations.element_id = p.ID
						AND wpml_translations.element_type = CONCAT('post_', p.post_type)";
	}

	protected function get_type_prefix() {
		return 'post_';
	}


	public function is_translated_type( $post_type ) {
		global $sitepress;

		return $sitepress->is_translated_post_type ( $post_type );
	}

	/**
	 * @param WP_Post $post
	 *
	 * @return string[] all language codes the post can be translated into
	 */
	public function get_allowed_target_langs( $post ) {
		global $sitepress;

		$active_languages = $sitepress->get_active_languages ();
		$can_translate    = array_keys ( $active_languages );
		$can_translate    = array_diff (
			$can_translate,
			array( $this->get_element_lang_code ( $post->ID ) )
		);

		return apply_filters ( 'wpml_allowed_target_langs', $can_translate, $post->ID, 'post' );
	}

	/**
	 * Before setting the language of the post to be saved, check if a translation in this language already exists
	 * This check is necessary, so that synchronization actions like thrashing or un-trashing of posts, do not lead to
	 * database corruption, due to erroneously changing a posts language into a state,
	 * where it collides with an existing translation. While the UI prevents this sort of action for the most part,
	 * this is not necessarily the case for other plugins like TM.
	 * The logic here first of all checks if an existing translation id is present in the desired language_code.
	 * If so but this translation is actually not the currently to be saved post,
	 * then this post will be saved to its current language. If the translation already exists,
	 * the existing translation id will be used. In all other cases a new entry in icl_translations will be created.
	 *
	 * @param Integer $trid
	 * @param String  $post_type
	 * @param String  $language_code
	 * @param Integer $post_id
	 * @param String  $source_language
	 */
	private function maybe_set_elid( $trid, $post_type, $language_code, $post_id, $source_language ) {
		global $sitepress;

		$element_type = 'post_' . $post_type;
		$sitepress->set_element_language_details (
			$post_id,
			$element_type,
			$trid,
			$language_code,
			$source_language
		);
	}

	/**
	 * @return WPML_Post_Synchronization
	 */
	private function get_sync_helper() {
		global $sitepress;

		$this->post_translation_sync = $this->post_translation_sync
			? $this->post_translation_sync : new WPML_Post_Synchronization( $this->settings, $this, $sitepress );

		return $this->post_translation_sync;
	}

	/**
	 * @return WPML_Debug_BackTrace
	 */
	private function get_debug_backtrace() {
		if ( ! $this->debug_backtrace ) {
			$this->debug_backtrace = new WPML\Utils\DebugBackTrace( 20 );
		}

		return $this->debug_backtrace;
	}

	public function set_debug_backtrace( WPML_Debug_BackTrace $debug_backtrace ) {
		$this->debug_backtrace = $debug_backtrace;
	}

	/**
	 * @return bool
	 */
	protected function is_inner_post_insertion() {
		$debug_backtrace = $this->get_debug_backtrace();
		return 1 < $debug_backtrace->count_function_in_call_stack( 'wp_insert_post' );
	}

	/**
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	protected function get_post_vars( $post ) {
		$post_vars = array();

		if ( ! $this->is_inner_post_insertion() ) {
			$post_vars = (array) $_POST;
		}

		foreach ( (array) $post as $k => $v ) {
			$post_vars[ $k ] = $v;
		}

		$post_vars['post_type'] = isset( $post_vars['post_type'] ) ? $post_vars['post_type'] : $post->post_type;

		return $post_vars;
	}

	/**
	 * @param bool $defer
	 */
	protected function defer_term_counting() {
		if ( ! self::$defer_term_counting ) {
			self::$defer_term_counting = true;
			wp_defer_term_counting( true );
		}
	}

	/**
	 * @return self|WPML_Frontend_Post_Actions|WPML_Admin_Post_Actions
	 */
	public static function getGlobalInstance() {
		global $wpml_post_translations, $sitepress;

		if ( ! isset( $wpml_post_translations ) ) {
			wpml_load_post_translation( is_admin(), $sitepress->get_settings() );
		}

		return $wpml_post_translations;
	}
}
