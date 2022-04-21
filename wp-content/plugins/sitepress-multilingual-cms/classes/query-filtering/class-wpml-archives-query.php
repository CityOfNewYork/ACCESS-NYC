<?php

use WPML\FP\Obj;

class WPML_Archives_Query implements IWPML_Frontend_Action, IWPML_DIC_Action {

	/** @var wpdb $wpdb */
	private $wpdb;

	/** @var WPML_Language_Where_Clause $language_where_clause */
	private $language_where_clause;

	/** @var SitePress */
	private $sitepress;

	public function __construct(
		wpdb $wpdb,
		WPML_Language_Where_Clause $language_where_clause,
		SitePress $sitepress
	) {
		$this->wpdb                  = $wpdb;
		$this->language_where_clause = $language_where_clause;
		$this->sitepress             = $sitepress;
	}

	public function add_hooks() {
		add_filter( 'getarchives_join', array( $this, 'get_archives_join' ), 10, 2 );
		add_filter( 'getarchives_where', array( $this, 'get_archives_where' ), 10, 2 );
	}

	/**
	 * @param string $join
	 * @param array $args
	 *
	 * @return string
	 */
	public function get_archives_join( $join, $args ) {
		$postType = esc_sql( Obj::propOr( 'post', 'post_type', $args ) );

		if ( $this->sitepress->is_translated_post_type( $postType ) ) {
			$join .= " JOIN {$this->wpdb->prefix}icl_translations wpml_translations ON wpml_translations.element_id = {$this->wpdb->posts}.ID AND wpml_translations.element_type='post_" . $postType . "'";
		}

		return $join;
	}

	/**
	 * @param string $where_clause
	 * @param array $args
	 *
	 * @return string
	 */
	public function get_archives_where( $where_clause, $args ) {
		return $where_clause . $this->language_where_clause->get( Obj::propOr( 'post', 'post_type', $args ) );
	}

}
