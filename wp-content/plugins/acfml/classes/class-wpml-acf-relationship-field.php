<?php

class WPML_ACF_Relationship_Field extends WPML_ACF_Field {

	public function has_element_with_display_translated($has_element_with_display_translated, $field) {
		global $sitepress_settings;

		if (isset($field['post_type']) && is_array($field['post_type'])) {
			if ($field['post_type'][0] == 'all') {
				$field['post_type'] = get_post_types();
			}
			foreach ($field['post_type'] as $type) {
				if (isset( $sitepress_settings['custom_posts_sync_option'][$type] ) && $sitepress_settings['custom_posts_sync_option'][$type] == 2) {
					$has_element_with_display_translated = true;
					break;
				}
			}
		}

		return $has_element_with_display_translated;
	}

	public function field_type() {
		return "relationship";
	}
}
