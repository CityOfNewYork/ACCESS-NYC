<?php

class WPML_PB_Cornerstone_Handle_Custom_Fields_Factory implements IWPML_Backend_Action_Loader, IWPML_AJAX_Action_Loader, IWPML_Frontend_Action_Loader {

	public function create() {
		return new WPML_PB_Handle_Custom_Fields( new WPML_Cornerstone_Data_Settings() );
	}
}