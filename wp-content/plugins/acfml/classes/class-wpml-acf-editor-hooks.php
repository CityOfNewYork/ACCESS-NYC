<?php

class WPML_ACF_Editor_Hooks {
	public function init_hooks() {
		add_filter( 'wpml_tm_editor_string_style', array( $this, 'wpml_tm_editor_string_style' ), 10, 3 );
	}

	public function wpml_tm_editor_string_style($field_style, $field_type, $original_post) {
		return $this->maybe_set_acf_wyswig_style($field_style, $field_type, $original_post);
	}

	private function maybe_set_acf_wyswig_style($field_style, $field_type, $original_post) {

		if ( preg_match_all('/field-(.+)-\d+/', $field_type, $matches, PREG_SET_ORDER, 0) !== false
		     &&	isset( $matches[0][1] )
		) {

			$field_name = $matches[0][1];

			$field_object = get_field_object($field_name, $original_post->ID);

			if ( isset( $field_object['type'] ) && "wysiwyg" == $field_object['type'] ) {
				$field_style = '2';
			}

		}

		return $field_style;
	}
}