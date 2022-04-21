<?php

class WPML_TM_Old_Editor_Factory implements IWPML_Backend_Action_Loader, IWPML_AJAX_Action_Loader {

	public function create() {
		return new WPML_TM_Old_Editor();
	}
}
