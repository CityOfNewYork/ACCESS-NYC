<?php

abstract class WPML_ACF_Field {
	public $meta_value;
	public $target_lang;
	public $meta_data;
	public $ids_object;
	public $related_acf_field_value;


	public function __construct($processed_data, $ids = null) {
		$this->meta_value = $processed_data->meta_value;
		$this->target_lang = $processed_data->target_lang;
		$this->meta_data = $processed_data->meta_data;
		$this->related_acf_field_value = $processed_data->related_acf_field_value;

		$this->ids_object = $ids;

	}
	
	public function convert_ids() {
		return $this->ids_object->convert($this);
	}

	public function has_element_with_display_translated($has_element_with_display_translated, $field) {
		global $sitepress_settings;

		if (isset($field['post_type']) && is_array($field['post_type'])) {
			foreach ($field['post_type'] as $type) {
				if (isset( $sitepress_settings['custom_posts_sync_option'][$type] ) && $sitepress_settings['custom_posts_sync_option'][$type] == 2) {
					$has_element_with_display_translated = true;
					break;
				}
			}
		}

		return $has_element_with_display_translated;
	}

	abstract function field_type();
}



