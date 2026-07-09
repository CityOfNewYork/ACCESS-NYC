<?php

class WPML_ST_Plugin_String_Scanner_Factory {

	/** @return WPML_Plugin_String_Scanner */
	public function create() {
		return new WPML_Plugin_String_Scanner( wpml_get_filesystem_direct() );
	}
}
