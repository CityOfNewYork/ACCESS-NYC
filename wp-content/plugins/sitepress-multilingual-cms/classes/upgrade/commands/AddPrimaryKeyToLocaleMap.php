<?php

namespace WPML\Upgrade\Commands;

class AddPrimaryKeyToLocaleMap extends AddPrimaryKeyToTable {

	protected function get_table() {
		return 'icl_locale_map';
	}

	protected function get_key_name() {
		return 'PRIMARY';
	}

	protected function get_key_columns() {
		return [ 'code', 'locale' ];
	}
}
