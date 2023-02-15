<?php

namespace WPML\TM\Upgrade\Commands;

class AddAteCommunicationRetryColumnToTranslationStatus extends \WPML_Upgrade_Add_Column_To_Table {
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
		return 'ate_comm_retry_count';
	}

	/**
	 * @return string
	 */
	protected function get_column_definition() {
		return "INT(11) UNSIGNED DEFAULT 0";
	}
}
