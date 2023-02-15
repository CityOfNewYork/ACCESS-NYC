<?php
/**
 * @author OnTheGo Systems
 */
class WPML_TM_Shortcodes_Catcher_Factory implements IWPML_Frontend_Action_Loader, IWPML_Backend_Action_Loader, IWPML_AJAX_Action_Loader {

	/**
	 * @return IWPML_Action|IWPML_Action[]|null
	 */
	public function create() {
		return new WPML_TM_Shortcodes_Catcher();
	}
}
