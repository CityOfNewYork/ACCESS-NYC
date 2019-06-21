<?php

/**
 * Some sites were not properly upgraded in 4.2.0.
 * In that case the old option was not deleted
 * and new site IDs were wrongly created.
 */
class WPML_Upgrade_WPML_Site_ID_Remaining implements IWPML_Upgrade_Command {

	/**
	 * @var string
	 *
	 * @see WPML_TM_ATE::SITE_ID_SCOPE
	 */
	const SCOPE_ATE = 'ate';

	/**
	 * @return bool
	 */
	public function run() {
		if ( $this->old_and_new_options_exist() ) {

			$value_from_old_option = get_option( WPML_Site_ID::SITE_ID_KEY, null );
			$value_from_new_option = get_option( WPML_Site_ID::SITE_ID_KEY . ':' . WPML_Site_ID::SITE_SCOPES_GLOBAL, null );

			// 1. We update the global option with the old value.
			update_option( WPML_Site_ID::SITE_ID_KEY . ':' . WPML_Site_ID::SITE_SCOPES_GLOBAL, $value_from_old_option, false );

			// 2. If the ate option has the same value as the new global, we also update it.
			if ( $this->option_exists( WPML_Site_ID::SITE_ID_KEY . ':' . self::SCOPE_ATE ) ) {
				$ate_uuid = get_option( WPML_Site_ID::SITE_ID_KEY . ':' . self::SCOPE_ATE, null );

				if ( $ate_uuid === $value_from_new_option ) {
					update_option( WPML_Site_ID::SITE_ID_KEY . ':' . self::SCOPE_ATE, $value_from_old_option, false );
				}
			}

			return delete_option( WPML_Site_ID::SITE_ID_KEY );
		}

		return true;
	}

	/**
	 * @return bool
	 */
	protected function old_and_new_options_exist() {
		return $this->option_exists( WPML_Site_ID::SITE_ID_KEY )
			&& $this->option_exists( WPML_Site_ID::SITE_ID_KEY . ':' . WPML_Site_ID::SITE_SCOPES_GLOBAL );
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	private function option_exists( $key ) {
		get_option( $key, null );
		$notoptions = wp_cache_get( 'notoptions', 'options' );

		return false === $notoptions || ! array_key_exists( $key, $notoptions );
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
