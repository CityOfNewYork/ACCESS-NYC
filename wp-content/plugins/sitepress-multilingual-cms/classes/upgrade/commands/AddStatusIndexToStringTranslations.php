<?php

namespace WPML\Upgrade\Commands;

class AddStatusIndexToStringTranslations extends AddIndexToTable {

	protected function get_table() {
		return 'icl_string_translations';
	}

	protected function get_index() {
		return 'status';
	}

	protected function get_index_definition() {
		return '( `status` )';
	}
}
