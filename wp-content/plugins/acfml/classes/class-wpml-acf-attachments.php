<?php

class WPML_ACF_Attachments {
	/**
	 * @var array Pairs of original - translation for already processed attachments during custom field copying.
	 */
	private static $attachment_fields_copied = array();

	/**
	 * Registers hooks related to attachments.
	 */
	public function register_hooks() {
		add_filter( 'acf/load_value/type=gallery', array( $this, 'load_translated_attachment' ) );
		add_filter( 'acf/load_value/type=image', array( $this, 'load_translated_attachment' ) );
		add_filter( 'acf/load_value/type=file', array( $this, 'load_translated_attachment' ) );
		add_action( 'wpml_after_update_attachment_texts', array( $this, 'copy_attachment_fields_to_translation' ), 10, 2 );
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

	/**
	 * Copies ACF custom fields from original attachment to translation.
	 *
	 * @param int    $original_id Original post ID.
	 * @param object $translation Translation object.
	 */
	public function copy_attachment_fields_to_translation( $original_id, $translation ) {
		if ( function_exists( 'get_fields' )
			&& function_exists( 'acf_get_field' )

			/*
			 * \WPML_Media_Attachments_Duplication::synchronize_attachment_metadata runs duplication twice,
			 * I don't know why it happens, but no reason to duplicate metadata again for the same attachments pair.
			 */
			&& ! isset( self::$attachment_fields_copied[ $original_id ][ $translation->element_id ] ) ) {
			$acf_fields = get_fields( $original_id );
			if ( is_array( $acf_fields ) ) {
				foreach ( $acf_fields as $acf_field_name => $acf_field_value ) {
					$acf_field = acf_get_field( $acf_field_name );
					if ( isset( $acf_field['wpml_cf_preferences'] ) ) {
						switch ( $acf_field['wpml_cf_preferences'] ) {
							case ( WPML_COPY_CUSTOM_FIELD ):
								update_post_meta( $translation->element_id, $acf_field_name, $acf_field_value );
								break;
							case ( WPML_COPY_ONCE_CUSTOM_FIELD ):
								$translated_post_meta = get_post_meta( $translation->element_id, $acf_field_name, true );
								if ( ! $translated_post_meta ) {
									update_post_meta( $translation->element_id, $acf_field_name, $acf_field_value );
								}
								break;
						}
					}
				}
			}
			self::$attachment_fields_copied[ $original_id ][ $translation->element_id ] = 1;
		}
	}
}