<?php

/**
 * Upgrades the former option to the new one.
 */
class WPML_TM_Upgrade_WPML_Site_ID_ATE implements IWPML_Upgrade_Command {

	/**
	 * Runs the upgrade process.
	 *
	 * @return bool
	 */
	public function run() {
		if ( $this->must_run() ) {
			$site_id = new WPML_Site_ID();

			$old_value = $site_id->get_site_id( WPML_Site_ID::SITE_SCOPES_GLOBAL );

			return update_option( WPML_Site_ID::SITE_ID_KEY . ':' . WPML_TM_ATE::SITE_ID_SCOPE, $old_value, false );
		}

		return true;
	}


	/**
	 * True if all conditions are met.
	 *
	 * @return bool
	 */
	private function must_run() {
		return WPML_TM_ATE_Status::is_enabled_and_activated() && (bool) get_option( WPML_TM_Wizard_Options::WIZARD_COMPLETE_FOR_MANAGER, false ) && $this->site_id_ate_does_not_exist();
	}

	/**
	 * Checks has the old option.
	 *
	 * @return bool
	 */
	protected function site_id_ate_does_not_exist() {
		get_option( WPML_Site_ID::SITE_ID_KEY . ':' . WPML_TM_ATE::SITE_ID_SCOPE, null );
		$notoptions = wp_cache_get( 'notoptions', 'options' );

		return ( array_key_exists( WPML_Site_ID::SITE_ID_KEY . ':' . WPML_TM_ATE::SITE_ID_SCOPE, $notoptions ) );
	}

	/**
	 * Runs in admin pages.
	 *
	 * @return bool
	 */
	public function run_admin() {
		return $this->run();
	}

	/**
	 * Unused.
	 *
	 * @return null
	 */
	public function run_ajax() {
		return null;
	}

	/**
	 * Unused.
	 *
	 * @return null
	 */
	public function run_frontend() {
		return null;
	}

	/**
	 * Unused.
	 *
	 * @return null
	 */
	public function get_results() {
		return null;
	}
}
