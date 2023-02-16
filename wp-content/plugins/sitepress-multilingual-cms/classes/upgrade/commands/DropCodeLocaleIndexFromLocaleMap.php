<?php

namespace WPML\Upgrade\Commands;

class DropCodeLocaleIndexFromLocaleMap extends DropIndexFromTable {

	protected function get_table() {
		return 'icl_locale_map';
	}

	protected function get_index() {
		return 'code';
	}
}
