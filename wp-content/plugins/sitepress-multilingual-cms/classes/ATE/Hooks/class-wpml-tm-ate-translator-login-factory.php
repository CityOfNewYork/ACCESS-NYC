<?php

/**
 * \WPML_TM_ATE_Translator_Login factory.
 *
 * @author OnTheGo Systems
 *
 * NOTE: This uses the Frontend loader because is_admin() returns false during wp_login
 */
class WPML_TM_ATE_Translator_Login_Factory implements IWPML_Frontend_Action_Loader {

	/**
	 * It returns an instance of WPML_TM_ATE_Translator_Login is ATE is enabled and active.
	 *
	 * @return \WPML_TM_ATE_Translator_Logine|\IWPML_Frontend_Action_Loader|null
	 */
	public function create() {
		if ( WPML_TM_ATE_Status::is_enabled_and_activated() ) {
			return WPML\Container\make( WPML_TM_ATE_Translator_Login::class );
		}

		return null;
	}

}
