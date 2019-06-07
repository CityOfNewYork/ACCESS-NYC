<?php

class WPML_ACF_Display_Translated {
	public function __construct() {
		add_filter( "acf/fields/relationship/query", array( $this, "allow_query_display_translated_in_wp_admin" ), 3, 10 );
		add_filter( "acf/fields/post_object/query", array( $this, "allow_query_display_translated_in_wp_admin" ), 3, 10 );
		add_filter( "acf/fields/taxonomy/query", array( $this, "allow_query_display_translated_in_wp_admin" ), 3, 10 );
	}

	public function allow_query_display_translated_in_wp_admin( $args, $field, $post_id ) {
		add_filter( "wpml_should_use_display_as_translated_snippet", array( $this, "query_should_use_display_as_translated" ), 2, 10 );
		return $args;
	}

	public function query_should_use_display_as_translated( $use_snippet, $post_types ) {
		if ( wpml_is_ajax() ) {
			$use_snippet = true;
		}

		return $use_snippet;
	}

}