<?php

class WPML_ACF_Attachments {
	public function register_hooks() {
		add_filter( 'acf/load_value/type=gallery', array( $this, 'load_translated_attachment' ), 10, 3 );
		add_filter( 'acf/load_value/type=image', array( $this, 'load_translated_attachment' ), 10, 3 );
		add_filter( 'acf/load_value/type=file', array( $this, 'load_translated_attachment' ), 10, 3 );
	}

	public function load_translated_attachment($value, $post_id, $field) {
		$newValue = $value;

		if ( is_serialized($value) ) { // Galleries come in serialized arrays
			$newValue = array();
			foreach ( maybe_unserialize($value) as $key => $id ) {
				$newValue[$key] = apply_filters( 'wpml_object_id', $id, 'attachment', true );
			}
		} else { // Single images arrive as simple values
			$newValue = apply_filters( 'wpml_object_id', $value, 'attachment', true );
		}

		return $newValue;
	}
}