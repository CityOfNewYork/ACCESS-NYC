<?php

namespace WPML\TM\Upgrade\Commands;

class AddReviewStatusColumnToTranslationStatus extends \WPML_Upgrade_Add_Column_To_Table {
	/**
	 * @return string
	 */
	protected function get_table() {
		return 'icl_translation_status';
	}

	/**
	 * @return string
	 */
	protected function get_column() {
		return 'review_status';
	}

	/**
	 * @return string
	 */
	protected function get_column_definition() {
		return "ENUM('NEEDS_REVIEW', 'EDITING', 'ACCEPTED')";
	}
}
