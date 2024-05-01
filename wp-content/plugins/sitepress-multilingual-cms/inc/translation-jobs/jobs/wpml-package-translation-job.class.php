<?php

require_once WPML_TM_PATH . '/inc/translation-jobs/jobs/wpml-element-translation-job.class.php';

class WPML_Package_Translation_Job extends WPML_Element_Translation_Job {
	public function get_original_document() {
		return apply_filters(
			'wpml_get_translatable_item',
			null,
			$this->get_original_element_id(),
			'package'
		);
	}

	public function get_url( $original = false ) {
		return '';
	}

	public function get_title() {
		return $this->get_title_from_db();
	}

	public function get_type_title() {
		return $this->get_title_from_db();
	}

	protected function load_resultant_element_id() {
		global $wpdb;
		$this->maybe_load_basic_data();

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT element_id
				FROM {$wpdb->prefix}icl_translations
				WHERE translation_id = %d
				LIMIT 1",
				$this->basic_data->translation_id
			)
		);
	}
}
