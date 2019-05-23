<?php

class WPML_TM_All_Admins_To_Translation_Managers implements IWPML_Action, IWPML_Backend_Action_Loader, IWPML_CLI_Action_Loader {

	const HAS_RUN_OPTION = 'WPML_Upgrade_All_Admins_To_Manage_Translations_Has_Run';

	public function create() {
		return $this;
	}

	public function add_hooks() {
		if ( ! get_option( self::HAS_RUN_OPTION ) ) {
			if ( ! did_action( 'wpml_tm_loaded' ) ) {
				add_action( 'wpml_tm_loaded', array( $this, 'upgrade_admin_caps' ) );
			} else {
				$this->upgrade_admin_caps();
			}
		}
		add_action( 'user_register', array( $this, 'upgrade_new_admin_to_manager' ) );
	}

	/**
	 * Upgrade all existing administrators to have Translation Manager capabilities.
	 * Also syncs with ATE via action
	 */
	public function upgrade_admin_caps() {
		$admins = get_users( array( 'role' => 'administrator' ) );
		foreach ( $admins as $user ) {
			$user->add_cap( WPML_Manage_Translations_Role::CAPABILITY );
		}
		do_action( 'wpml_tm_ate_synchronize_managers' );

		update_option( self::HAS_RUN_OPTION, true );
	}

	/**
	 * Upgrade new administrator user to have Translation Manager capabilities.
	 * Also syncs with ATE via action
	 *
	 * @param int $user_id
	 */
	public function upgrade_new_admin_to_manager( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( in_array( 'administrator', $user->roles ) ) {
			$user->add_cap( WPML_Manage_Translations_Role::CAPABILITY );
			do_action( 'wpml_tm_ate_synchronize_managers' );
		}
	}
}
