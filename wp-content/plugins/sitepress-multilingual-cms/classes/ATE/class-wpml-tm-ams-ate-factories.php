<?php

/**
 * Used for helping building other factories.
 *
 * @see    Usage.
 *
 * @author OnTheGo Systems
 */
class WPML_TM_AMS_ATE_Factories {

	/**
	 * It returns an cached instance of \WPML_TM_ATE_API.
	 *
	 * @return \WPML_TM_ATE_API
	 */
	public function get_ate_api() {
		return WPML\Container\make( WPML_TM_ATE_API::class );
	}

	/**
	 * It returns an cached instance of \WPML_TM_ATE_API.
	 *
	 * @return \WPML_TM_AMS_API
	 */
	public function get_ams_api() {
		return WPML\Container\make( WPML_TM_AMS_API::class );
	}

	/**
	 * If ATE is active, it returns true.
	 *
	 * @return bool
	 */
	public function is_ate_active() {
		if ( ! WPML_TM_ATE_Status::is_active() ) {
			try {
				$this->get_ams_api()->get_status();

				return WPML_TM_ATE_Status::is_active();
			} catch ( Exception $ex ) {
				return false;
			}
		}

		return true;
	}
}
