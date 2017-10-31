<?php

/**
 * Created by PhpStorm.
 * User: konrad
 * Date: 12.01.17
 * Time: 12:36
 */
class WPML_ACF_Pro {

	public function __construct() {
		add_action('icl_make_duplicate', array($this, 'make_duplicate_action'), 10, 4);
		add_filter('acf/duplicate_field/type=clone', array($this, 'duplicate_clone_field'), 10, 1);
	}

	public function make_duplicate_action($master_post_id, $lang, $post_array, $id) {
		if (isset($post_array['post_type']) && 'acf-field-group' == $post_array['post_type']) {
			$args = array(
				'post_parent' => $id,
				'post_type'   => 'acf-field',
				'numberposts' => -1
			);

			$posts = get_posts($args);

			if (is_array($posts) && count($posts) > 0) {
				foreach($posts as $post) {
					wp_delete_post($post->ID, true);
				}
			}
		}

	}

	/**
	 * Filter: Update clloned field names to translated version
	 *
	 * @param $field
	 *
	 * @return updated field
	 */
	public function duplicate_clone_field($field) {
		$parent_language = apply_filters('wpml_post_language_details', null, $field['parent']);
		foreach ($field['clone'] as $id => $key_post_name) {
			if (strpos($key_post_name, 'group_') === 0) {
				$original_id = $this->get_post_id_by_name($key_post_name);
				$translated_id = apply_filters('wpml_object_id', $original_id, 'acf-field-group', false, $parent_language['language_code']);
				if ($translated_id) {
					$post = get_post($translated_id);
					if (isset($post->post_name)) {
						$field['clone'][$id] = $post->post_name;
					}
				}
			}
		}

		return $field;
	}

	private function get_post_id_by_name($post_name, $output = OBJECT) {
		global $wpdb;
		$post_id = null;
		$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s", $post_name ));

		return $post_id;
	}

}