<?php

class WPML_TM_AMS_Synchronize_Users_On_Access_Denied_Factory implements IWPML_Backend_Action_Loader {
	public function create() {
		return new WPML_TM_AMS_Synchronize_Users_On_Access_Denied();
	}

}