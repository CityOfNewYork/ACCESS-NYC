<?php

/**
 * Class WPML_Adjacent_Links_Hooks
 *
 * @author OnTheGoSystems
 */
class WPML_Adjacent_Links_Hooks implements IWPML_Action {

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var wpdb $wpdb */
	private $wpdb;

	/**
	 * WPML_Adjacent_Links_Hooks constructor.
	 *
	 * @param SitePress $sitepress
	 * @param wpdb      $wpdb
	 */
	public function __construct( SitePress $sitepress, wpdb $wpdb ) {
		$this->sitepress = $sitepress;
		$this->wpdb      = $wpdb;
	}

	public function add_hooks() {
		add_filter( 'get_previous_post_join', array( $this, 'get_adjacent_post_join' ) );
		add_filter( 'get_next_post_join', array( $this, 'get_adjacent_post_join' ) );
		add_filter( 'get_previous_post_where', array( $this, 'get_adjacent_post_where' ) );
		add_filter( 'get_next_post_where', array( $this, 'get_adjacent_post_where' ) );
	}

	/**
	 * @param string $join_clause
	 *
	 * @return string
	 */
	function get_adjacent_post_join( $join_clause ) {
		$post_type = $this->get_current_post_type();

		$cache_key   = md5( wp_json_encode( array( $post_type, $join_clause ) ) );
		$cache_group = 'adjacent_post_join';
		$join_cached = wp_cache_get( $cache_key, $cache_group );

		if ( $join_cached ) {
			return $join_cached;
		}

		if ( $this->sitepress->is_translated_post_type( $post_type ) ) {
			$join_clause .= $this->wpdb->prepare(
				" JOIN {$this->wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type = %s",
				'post_' . $post_type
			);
		}

		wp_cache_set( $cache_key, $join_clause, $cache_group );

		return $join_clause;
	}

	/**
	 * @param string $where_clause
	 *
	 * @return string
	 */
	function get_adjacent_post_where( $where_clause ) {
		$post_type = $this->get_current_post_type();

		$cache_key    = md5( wp_json_encode( array( $post_type, $where_clause ) ) );
		$cache_group  = 'adjacent_post_where';
		$where_cached = wp_cache_get( $cache_key, $cache_group );

		if ( $where_cached ) {
			return $where_cached;
		}

		if ( $this->sitepress->is_translated_post_type( $post_type ) ) {
			$where_clause .= $this->wpdb->prepare( " AND language_code = '%s'", $this->sitepress->get_current_language() );
		}

		wp_cache_set( $cache_key, $where_clause, $cache_group );

		return $where_clause;
	}

	/** @return string */
	private function get_current_post_type() {
		$post_type = get_query_var( 'post_type' );

		if ( ! $post_type ) {
			$post_type = get_post_type();
		}

		if ( ! $post_type ) {
			$post_type = 'post';
		}

		return $post_type;
	}
}
