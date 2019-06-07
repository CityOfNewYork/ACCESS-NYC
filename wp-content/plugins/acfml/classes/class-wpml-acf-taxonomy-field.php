<?php

class WPML_ACF_Taxonomy_Field extends WPML_ACF_Field {

	public function has_element_with_display_translated($has_element_with_display_translated, $field) {
		global $sitepress_settings;

		if (isset($field['taxonomy'])) {
			$field['taxonomy'] = (array) $field['taxonomy'];
			foreach ($field['taxonomy'] as $taxonomy) {
				if (isset( $sitepress_settings['taxonomies_sync_option'][$taxonomy] ) && $sitepress_settings['taxonomies_sync_option'][$taxonomy] == 2) {
					$has_element_with_display_translated = true;
					break;
				}
			}
		}

		return $has_element_with_display_translated;
	}

	public function field_type() {
		return "taxonomy";
	}

}
