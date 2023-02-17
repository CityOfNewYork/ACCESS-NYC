<?php

namespace WPML\TM\Upgrade\Commands;

class AddAteSyncCountToTranslationJob extends \WPML_Upgrade_Add_Column_To_Table {
	/**
	 * @return string
	 */
	protected function get_table() {
		return 'icl_translate_job';
	}

	/**
	 * @return string
	 */
	protected function get_column() {
		return 'ate_sync_count';
	}

	/**
	 * @return string
	 */
	protected function get_column_definition() {
		return "INT(6) UNSIGNED DEFAULT 0";
	}
}
