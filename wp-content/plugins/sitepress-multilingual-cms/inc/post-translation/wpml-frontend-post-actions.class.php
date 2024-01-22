<?php

use WPML\LIB\WP\Hooks;

class WPML_Frontend_Post_Actions extends WPML_Post_Translation {

	public function init() {
		parent::init ();
		if ( $this->is_setup_complete() ) {

			/**
			 * In theory, we could add the 'delete_post` action for all frontend requests
			 * We'll limit it to REST to avoid any unexpected problems
			 */
			Hooks::onAction( 'rest_api_init' )
			     ->then( function () {
				     add_action( 'delete_post', [ $this, 'delete_post_actions' ] );
			     } );
		}
	}

	/**
	 * @param int    $post_id
	 * @param string $post_status
	 *
	 * @return null|int
	 */
	function get_save_post_trid( $post_id, $post_status ) {

		return $this->get_element_trid( $post_id );
	}

	/**
	 * @param int     $pidd
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function save_post_actions( $pidd, $post ) {
		global $sitepress;

		$this->defer_term_counting();
		if ( ! $post ) {
			$post = get_post( $pidd );
		}

		$http_referer = new WPML_URL_HTTP_Referer( new WPML_Rest( new WP_Http() ) );
		// exceptions
		if ( ! $this->has_save_post_action( $post ) || $http_referer->is_rest_request_called_from_post_edit_page() ) {
			return;
		}
		$default_language       = $sitepress->get_default_language();
		$post_vars              = $this->get_post_vars( $post );
		$post_id                = isset( $post_vars['post_ID'] ) ? $post_vars['post_ID']
			: $pidd; //latter case for XML-RPC publishing
		$language_code          = $this->get_save_post_lang( $post_id, $sitepress );
		$trid                   = $this->get_save_post_trid( $post_id, $post->post_status );
		// after getting the right trid set the source language from it by referring to the root translation
		// of this trid, in case no proper source language has been set yet
		$source_language = $this->get_save_post_source_lang( $trid, $language_code, $default_language );
		$this->after_save_post( $trid, $post_vars, $language_code, $source_language );
	}

	protected function get_save_post_source_lang( $trid, $language_code, $default_language ) {
		$post_id = $this->get_element_id ( $trid, $language_code );

		return $post_id ? $this->get_source_lang_code ( $post_id ) : null;
	}
}