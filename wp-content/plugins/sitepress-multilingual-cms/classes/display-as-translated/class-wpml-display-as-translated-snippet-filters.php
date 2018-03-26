<?php

class WPML_Display_As_Translated_Snippet_Filters implements IWPML_Action {

	public function add_hooks() {
		add_filter( 'wpml_should_use_display_as_translated_snippet', array( $this, 'filter_post_types' ), 10, 2 );
	}

	public function filter_post_types( $should_use_snippet, array $post_type ) {
		if ( ! $should_use_snippet ) {
			return false !== strpos( $_SERVER['REQUEST_URI'], 'admin-ajax' )
			       && isset( $_REQUEST['action'] ) && 'query-attachments' === $_REQUEST['action']
			       && array_key_exists( 'attachment', $post_type );
		}

		return $should_use_snippet;
	}
}