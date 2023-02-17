<?php

namespace WPML\Compatibility\Divi;

use SitePress;

class ThemeBuilder implements \IWPML_Action {

	/** @var SitePress */
	private $sitepress;

	/**
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/**
	 * Add filters and actions.
	 */
	public function add_hooks() {
		if ( ! defined( 'ET_THEME_BUILDER_DIR' ) ) {
			return;
		}

		if ( $this->sitepress->is_setup_complete() ) {
			if ( is_admin() ) {
				add_action( 'init', [ $this, 'make_layouts_editable' ], 1000 ); // Before WPML_Sticky_Links::init.
				add_filter( 'wpml_document_view_item_link', [ $this, 'document_view_layout_link' ], 10, 5 );
			} else {
				add_filter( 'get_post_metadata', [ $this, 'translate_layout_ids' ], 10, 4 );
			}
		}
	}

	/**
	 * Gets all post types that are layouts.
	 */
	private static function get_types() {
		return [
			ET_THEME_BUILDER_HEADER_LAYOUT_POST_TYPE,
			ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE,
			ET_THEME_BUILDER_FOOTER_LAYOUT_POST_TYPE,
		];
	}

	/**
	 * Access the global post types array to tweak the settings for layouts
	 */
	public function make_layouts_editable() {
		global $wp_post_types;

		foreach ( $this->get_types() as $type ) {
			$wp_post_types[ $type ]->show_ui      = true;
			$wp_post_types[ $type ]->show_in_menu = false;
			$wp_post_types[ $type ]->_edit_link   = 'post.php?post=%d';
		}
	}

	/**
	 * Translate theme builder layout ids in the frontend.
	 *
	 * @param string $value   The layout id.
	 * @param int    $post_id The post it belongs to.
	 * @param string $key     The meta key we are handling.
	 * @param bool   $single  Fetch a single row or an array.
	 * @return string
	 */
	public function translate_layout_ids( $value, $post_id, $key, $single ) {

		if ( in_array( $key, [ '_et_header_layout_id', '_et_body_layout_id', '_et_footer_layout_id' ], true ) ) {
			/**
			 * The `get_post_metadata` filter provides `null` as the initial `$value`.
			 * When we return a different $value it is used directly, to avoid a second query.
			 * This means that we have to get the original value first, removing ourselves so
			 * we don't fall into an infinite loop.
			 */
			remove_filter( 'get_post_metadata', [ $this, 'translate_layout_ids' ], 10 );
			$original_id = get_post_meta( $post_id, $key, true );
			add_filter( 'get_post_metadata', [ $this, 'translate_layout_ids' ], 10, 4 );

			$type  = substr( $key, 1, -3 );
			$value = $this->sitepress->get_object_id( $original_id, $type, true );

			if ( ! $single ) {
				$value = [ $value ];
			}
		}

		return $value;
	}

	/**
	 * Remove the 'View' link because you can't view layouts alone.
	 *
	 * @param string $link   The complete link.
	 * @param string $text   The text to link.
	 * @param object $job    The corresponding translation job.
	 * @param string $prefix The prefix of the element type.
	 * @param string $type   The element type.
	 *
	 * @return string
	 */
	public function document_view_layout_link( $link, $text, $job, $prefix, $type ) {
		if ( 'post' === $prefix && $this->is_theme_layout( $type ) ) {
			$link = '';
		}

		return $link;
	}

	/**
	 * Check if a certain Type is a theme builder layout.
	 *
	 * @param string $type The type to check.
	 *
	 * @return bool
	 */
	private function is_theme_layout( $type ) {
		return in_array( $type, $this->get_types(), true );
	}
}
