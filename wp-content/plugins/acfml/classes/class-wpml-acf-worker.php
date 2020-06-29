<?php
class WPML_ACF_Worker {
	/**
	 * @var WPML_ACF_Duplicated_Post
	 */
	private $duplicated_post;

	/**
	 * WPML_ACF_Worker constructor.
	 *
	 * @param WPML_ACF_Duplicated_Post $duplicated_post
	 */
	public function __construct( WPML_ACF_Duplicated_Post $duplicated_post ) {
		$this->duplicated_post = $duplicated_post;
		$this->register_hooks();
	}

	public function register_hooks() {
		add_filter('wpml_duplicate_generic_string', array($this, 'duplicate_post_meta'), 10, 3);
		add_filter('wpml_sync_parent_for_post_type', array($this, 'sync_parent_for_post_type'), 10, 2);
		add_action('wpml_after_copy_custom_field', array($this, 'after_copy_custom_field'), 10, 3);
	}

	/**
	 * When custom field has been copied, adjusts its values to represent translated objects.
	 *
	 * @param int    $post_id_from The ID of the original post.
	 * @param int    $post_id_to   The ID of translated post.
	 * @param string $meta_key     The meta key of copied custom field.
	 */
	public function after_copy_custom_field($post_id_from, $post_id_to, $meta_key) {
		$field = acf_get_field( $meta_key );
		if ( $field ) {
			$meta_value  = get_post_meta( $post_id_to, $meta_key, true );
			$target_lang = $this->get_target_lang( $post_id_to );
			if ( $target_lang ) {
				$meta_data            = $this->prepare_metadata( $meta_value, $meta_key, $post_id_from, $post_id_to );
				$meta_value_converted = $this->duplicate_post_meta( $meta_value, $target_lang, $meta_data );
				if ( $meta_value !== $meta_value_converted ) {
					update_post_meta( $post_id_to, $meta_key, $meta_value_converted, $meta_value );
				}
			}
		}
	}

	public function duplicate_post_meta($meta_value, $target_lang, $meta_data) {

		$processed_data = new WPML_ACF_Processed_Data($meta_value, $target_lang, $meta_data);

		$field = $this->duplicated_post->resolve_field($processed_data);

		$meta_value_converted = $field->convert_ids();

		return $meta_value_converted;
	}

	public function sync_parent_for_post_type($sync, $post_type) {
		if ("acf-field" == $post_type || "acf-field-group" == $post_type) {
			$sync = false;
		}

		return $sync;
	}

	/**
	 * Prepares metadata to has the same format as used in \WPML_Post_Duplication::duplicate_custom_fields.
	 *
	 * @see \WPML_Post_Duplication::duplicate_custom_fields
	 *
	 * @param string $meta_value   The meta value of processed custom field.
	 * @param string $meta_key     The meta key of processed custom field.
	 * @param int    $post_id_from The ID of original post.
	 * @param int    $post_id_to   The ID of translated post.
	 *
	 * @return array The metadata.
	 */
	public function prepare_metadata( $meta_value, $meta_key, $post_id_from, $post_id_to ) {
		$is_serialized = is_serialized( $meta_value );
		return [
			'context'        => 'custom_field',
			'attribute'      => 'value',
			'key'            => $meta_key,
			'is_serialized'  => $is_serialized,
			'post_id'        => $post_id_to,
			'master_post_id' => $post_id_from,
		];
	}

	/**
	 * Returns target language code.
	 *
	 * First tries to take it from wpml_element_langauge_code, if it fails check if language code is stored in the
	 * $_POST data (as it happens when post is translated as part of translation job on CTE).
	 *
	 * @param int $target_post_id The ID the translated post.
	 *
	 * @return mixed|void|null The language code or null.
	 */
	private function get_target_lang( $target_post_id ) {
		$args['element_id']   = $target_post_id;
		$args['element_type'] = get_post_type( $target_post_id );
		$target_lang          = apply_filters( 'wpml_element_language_code', null, $args );
		if ( ! $target_lang && wp_verify_nonce( $_POST['_icl_nonce'], 'wpml_save_job_nonce' ) ) {
			$target_lang = isset( $_POST['lang'] ) ? filter_var( $_POST['lang'], FILTER_SANITIZE_STRING ) : null;
		}
		return $target_lang;
	}

}
