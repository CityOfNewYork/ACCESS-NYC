<?php

namespace WPML\PB\SiteOrigin;

class HandleCustomFieldsFactory implements \IWPML_Backend_Action_Loader, \IWPML_AJAX_Action_Loader, \IWPML_Frontend_Action_Loader {

	/**
	 * @return \WPML_PB_Handle_Custom_Fields
	 */
	public function create() {
		return new \WPML_PB_Handle_Custom_Fields( new DataSettings() );
	}

}
