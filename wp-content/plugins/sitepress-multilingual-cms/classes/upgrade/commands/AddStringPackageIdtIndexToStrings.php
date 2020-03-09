<?php

namespace WPML\Upgrade\Commands;

class AddStringPackageIdIndexToStrings extends AddIndexToTable {

	protected function get_table() {
		return 'icl_strings';
	}

	protected function get_index() {
		return 'string_package_id';
	}

	protected function get_index_definition() {
		return '( `string_package_id` )';
	}
}
