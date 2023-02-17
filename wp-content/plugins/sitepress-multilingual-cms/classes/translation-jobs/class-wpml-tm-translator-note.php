<?php

class WPML_TM_Translator_Note {

	const META_FIELD_KEY = '_icl_translator_note';

	public static function get( $post_id ) {
		return get_post_meta( $post_id, self::META_FIELD_KEY, true );
	}

	public static function update( $post_id, $note ) {
		update_post_meta( $post_id, self::META_FIELD_KEY, $note );
	}
}
