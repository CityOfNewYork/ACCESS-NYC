<?php

class WPML_ACF_Attachments {
	public function register_hooks() {
		add_filter( 'acf/load_value/type=gallery', array( $this, 'load_translated_attachment' ) );
		add_filter( 'acf/load_value/type=image', array( $this, 'load_translated_attachment' ) );
		add_filter( 'acf/load_value/type=file', array( $this, 'load_translated_attachment' ) );
	}

	/**
	 * @param array|string|int $attachments array of attachment ids or single attachment id
	 *
	 * @return array|string|int translated attachemnt id or attachments' ids
	 */
	public function load_translated_attachment($attachments) {
		$attachments = maybe_unserialize($attachments);
		if ( is_array( $attachments ) ) {
			$translated_attachments = array();
			foreach ( $attachments as $key => $attachment_id ) {
				$translated_attachments[$key] = apply_filters( 'wpml_object_id', $attachment_id, 'attachment', true );
			}
		} else {
			$translated_attachments = apply_filters( 'wpml_object_id', $attachments, 'attachment', true );
		}

		return $translated_attachments;
	}
}