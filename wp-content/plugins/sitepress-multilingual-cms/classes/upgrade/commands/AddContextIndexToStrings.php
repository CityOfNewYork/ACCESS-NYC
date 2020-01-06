<?php

namespace WPML\Upgrade\Commands;

class AddContextIndexToStrings extends AddIndexToTable {

	protected function get_table() {
		return 'icl_strings';
	}

	protected function get_index() {
		return 'context';
	}

	protected function get_index_definition() {
		return '( `context` )';
	}
}
