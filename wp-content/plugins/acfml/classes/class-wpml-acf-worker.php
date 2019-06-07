<?php
class WPML_ACF_Worker {
	private $duplicated_post;

	public function __construct($duplicated_post) {
		$this->register_hooks();
		$this->duplicated_post = $duplicated_post;
	}

	public function register_hooks() {
		add_filter('wpml_duplicate_generic_string', array($this, 'duplicate_post_meta'), 10, 3);
		add_filter('wpml_sync_parent_for_post_type', array($this, 'sync_parent_for_post_type'), 10, 2);
		add_action('wpml_after_copy_custom_field', array($this, 'after_copy_custom_field'), 10, 3);
	}

	public function after_copy_custom_field($post_id_from, $post_id_to, $meta_key) {

		$meta_value = get_post_meta($post_id_to, $meta_key, true);

		$args['element_id'] = $post_id_to;
		$args['element_type'] = get_post_type($post_id_to);
		$target_lang = apply_filters( 'wpml_element_language_code', null, $args );

		$is_serialized = is_serialized( $meta_value );
		$meta_data     = array(
			'context'        => 'custom_field',
			'attribute'      => 'value',
			'key'            => $meta_key,
			'is_serialized'  => $is_serialized,
			'post_id'        => $post_id_to,
			'master_post_id' => $post_id_from
		);

		$meta_value_converted = $this->duplicate_post_meta($meta_value, $target_lang, $meta_data);

		if ( $meta_value !== $meta_value_converted ) {
    	update_post_meta($post_id_to, $meta_key, $meta_value_converted, $meta_value);
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

}
