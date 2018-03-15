<?php

class WPML_Get_Page_By_Path {

	/** @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-4918 */
	const BEFORE_REMOVE_PLACEHOLDER_ESCAPE_PRIORITY = -1;

	/** @var wpdb $wpdb */
	private $wpdb;

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var WPML_Debug_BackTrace $debug_backtrace */
	private $debug_backtrace;

	private $language;

	public function __construct( wpdb $wpdb, SitePress $sitepress, WPML_Debug_BackTrace $debug_backtrace ) {
		$this->wpdb            = $wpdb;
		$this->sitepress       = $sitepress;
		$this->debug_backtrace = $debug_backtrace;
	}

	public function get( $page_name, $lang, $output = OBJECT, $post_type = 'page' ) {
		$this->language = $lang;
		add_filter( 'query', array( $this, 'get_page_by_path_filter' ), self::BEFORE_REMOVE_PLACEHOLDER_ESCAPE_PRIORITY );
		$temp_lang_switch = new WPML_Temporary_Switch_Language( $this->sitepress, $lang );
		$page = get_page_by_path( $page_name, $output, $post_type );
		$temp_lang_switch->restore_lang();
		remove_filter( 'query', array( $this, 'get_page_by_path_filter' ), self::BEFORE_REMOVE_PLACEHOLDER_ESCAPE_PRIORITY );
		return $page;
	}

	public function get_page_by_path_filter( $query ) {
		if ( $this->debug_backtrace->is_function_in_call_stack( 'get_page_by_path' ) ) {

			$where = $this->wpdb->prepare( "ID IN ( SELECT element_id FROM {$this->wpdb->prefix}icl_translations WHERE language_code = %s AND element_type LIKE 'post_%%' ) AND ", $this->language );

			$query = str_replace( "WHERE ", "WHERE " . $where, $query );
		}

		return $query;
	}
}
