<?php

class WPML_Tax_Menu_Loader {

	/** @var SitePress $sitepress */
	private $sitepress;
	/** @var WPDB $wpdb */
	public $wpdb;
	/** @var string $taxonomy */
	private $taxonomy;

	/**
	 * @param wpdb      $wpdb
	 * @param SitePress $sitepress
	 * @param string    $taxonomy
	 */
	public function __construct( $wpdb, $sitepress, $taxonomy ) {
		$this->sitepress = $sitepress;
		$this->wpdb      = $wpdb;
		$this->taxonomy  = $taxonomy;

		add_action( 'init', [ $this, 'init' ] );
		add_action( 'after-category-table', [ $this, 'category_display_action' ], 1, 0 );
		add_filter( 'wp_redirect', [ $this, 'preserve_lang_param' ] );
	}

	public function get_wpdb() {
		return $this->wpdb;
	}

	public function init() {
		$tax_get = filter_input( INPUT_GET, 'taxonomy' );
		$trid    = filter_input( INPUT_GET, 'trid' );
		if ( $trid
			 && ( $source_lang = filter_input( INPUT_GET, 'source_lang' ) )
			 && get_taxonomy( $tax_get ) !== false
		) {
			$translations = $this->sitepress->get_element_translations( $trid, 'tax_' . $this->taxonomy );
			if ( isset( $translations[ $_GET['lang'] ] ) && ! empty( $translations[ $_GET['lang'] ]->term_id ) ) {
				wp_redirect( get_edit_term_link( $translations[ $_GET['lang'] ]->term_id, $tax_get ) );
				exit;
			} else {
				add_action( 'admin_notices', [ $this, '_tax_adding' ] );
			}
		}

		add_action( $this->taxonomy . '_edit_form', [ $this, 'wpml_edit_term_form' ] );
		add_action( $this->taxonomy . '_add_form', [ $this, 'wpml_edit_term_form' ] );

		add_action( 'admin_print_scripts-edit-tags.php', [ $this, 'js_scripts_tags' ] );
		add_action( 'admin_print_scripts-term.php', [ $this, 'js_scripts_tags' ] );
		add_filter( 'wp_dropdown_cats', [ $this, 'wp_dropdown_cats_select_parent' ], 10, 2 );
		if ( ! $this->sitepress->get_wp_api()->is_term_edit_page() ) {
			$term_lang_filter = new WPML_Term_Language_Filter( $this->wpdb, $this->sitepress );
			add_action( 'admin_footer', [ $term_lang_filter, 'terms_language_filter' ], 0 );
		}
	}

	/**
	 * Filters the display of the categories list in order to prevent the default category from being delete-able.
	 * This is done by printing a hidden div containing a JSON encoded array with all category id's, the checkboxes of which are to be removed.
	 */
	public function category_display_action() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $wpml_term_translations;

		if ( ( $default_category_id = get_option( 'default_category' ) ) ) {
			$default_cat_ids = array();

			$translations = $wpml_term_translations->get_element_translations( $default_category_id );
			foreach ( $translations as $lang => $translation ) {
				$default_cat_ids [] = $wpml_term_translations->term_id_in( $default_category_id, $lang );
			}
			echo '<div id="icl-default-category-ids" style="display: none;">'
				 . wp_json_encode( $default_cat_ids ) . '</div>';
		}
	}

	public function js_scripts_tags() {
		wp_enqueue_script( 'sitepress-tags', ICL_PLUGIN_URL . '/res/js/tags.js', array(), ICL_SITEPRESS_VERSION );
	}

	function wp_dropdown_cats_select_parent( $html, $args ) {
		if ( ( $trid = filter_input( INPUT_GET, 'trid', FILTER_SANITIZE_NUMBER_INT ) ) ) {
			$element_type     = $taxonomy = isset( $args['taxonomy'] ) ? $args['taxonomy'] : 'post_tag';
			$icl_element_type = 'tax_' . $element_type;
			$source_lang      = isset( $_GET['source_lang'] )
				? filter_input( INPUT_GET, 'source_lang', FILTER_SANITIZE_FULL_SPECIAL_CHARS )
				: $this->sitepress->get_default_language();
			$parent           = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"
				SELECT parent
				FROM {$this->wpdb->term_taxonomy} tt
					JOIN {$this->wpdb->prefix}icl_translations tr ON tr.element_id=tt.term_taxonomy_id
                    AND tr.element_type=%s AND tt.taxonomy=%s
				WHERE trid=%d AND tr.language_code=%s
			",
					$icl_element_type,
					$taxonomy,
					$trid,
					$source_lang
				)
			);
			if ( $parent ) {
				$parent = (int) icl_object_id( $parent, $element_type );
				$html   = str_replace( 'value="' . $parent . '"', 'value="' . $parent . '" selected="selected"', $html );
			}
		}

		return $html;
	}

	/**
	 * @param Object $term
	 */
	public function wpml_edit_term_form( $term ) {
		include WPML_PLUGIN_PATH . '/menu/term-taxonomy-menus/taxonomy-menu.php';
	}

	function _tax_adding() {
		$trid         = filter_input( INPUT_GET, 'trid', FILTER_SANITIZE_NUMBER_INT );
		$taxonomy     = filter_input( INPUT_GET, 'taxonomy' );
		$translations = $trid && $taxonomy ?
			$this->sitepress->get_element_translations( $trid, 'tax_' . $taxonomy ) : array();
		$name         = isset( $translations[ $_GET['source_lang'] ] ) ? $translations[ filter_input( INPUT_GET, 'source_lang', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ]
			: false;
		$name         = isset( $name->name ) ? $name->name : false;
		if ( $name !== false ) {
			$tax_name = apply_filters( 'the_category', $name );
			// translators: %s is replaced by the name of the taxonomy.
			echo '<div id="icl_tax_adding_notice" class="updated fade"><p>'
				 . sprintf( esc_html__( 'Adding translation for: %s.', 'sitepress' ), $tax_name )
				 . '</p></div>';
		}
	}

	/**
	 * If user perform bulk taxonomy deletion when displaying non-default
	 * language taxonomies, after deletion should stay with same language
	 *
	 * @param string $location Url where browser will redirect.
	 * @return string Url where browser will redirect.
	 */
	public function preserve_lang_param( $location ) {
		global $wpml_url_converter;

		$get_lang = $wpml_url_converter->get_language_from_url(
			(string) filter_input( INPUT_POST, '_wp_http_referer' )
		);
		$location = $get_lang ? add_query_arg( 'lang', $get_lang, $location ) : $location;

		return $location;
	}
}
