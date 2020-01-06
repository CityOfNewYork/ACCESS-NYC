<?php

/**
 * Class WPML_Create_Post_Helper
 *
 * @since 3.2
 */
class WPML_Create_Post_Helper {

	/** @var SitePress $sitepress */
	private $sitepress;

	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/**
	 * @param array       $postarr will be escaped inside the method
	 * @param string|null $lang
	 * @param bool        $wp_error
	 *
	 * @return int|WP_Error
	 */
	public function insert_post( array $postarr, $lang = null, $wp_error = false ) {
		$current_language = null;
		$postarr          = $this->slash_and_preserve_tag_ids( $postarr );

		if ( $lang ) {
			$current_language = $this->sitepress->get_current_language();
			$this->sitepress->switch_lang( $lang, false );
		}

		if ( isset( $postarr['ID'] ) ) {
			$new_post_id = wp_update_post( $postarr, $wp_error );
		} else {
			add_filter( 'wp_insert_post_empty_content', array( $this, 'allow_empty_post' ), 10, 0 );
			$new_post_id = wp_insert_post( $postarr, $wp_error );
			remove_filter( 'wp_insert_post_empty_content', array( $this, 'allow_empty_post' ) );

		}

		if ( $current_language ) {
			$this->sitepress->switch_lang( $current_language, false );
		}

		return $new_post_id;
	}

	public function allow_empty_post() {
		return false; // We need to return false to indicate that the post is not empty
	}

	/**
	 * We need to make sure that tag IDs are not casted into strings.
	 * This is a side effect of https://core.trac.wordpress.org/ticket/45121
	 * (wp_update_post() can modify post tag) for which we have
	 * a temporary fix in `\WPML_Page_Builders_Media_Shortcodes_Update::translate`.
	 *
	 * @param array $postarr
	 *
	 * @return array
	 */
	private function slash_and_preserve_tag_ids( array $postarr ) {
		if ( array_key_exists( 'tags_input', $postarr ) ) {
			$tagIds  = array_filter( $postarr['tags_input'], 'is_int' );
			$postarr = wp_slash( $postarr );
			$postarr['tags_input'] = $tagIds + $postarr['tags_input'];
		} else {
			$postarr = wp_slash( $postarr );
		}

		return $postarr;
	}
}