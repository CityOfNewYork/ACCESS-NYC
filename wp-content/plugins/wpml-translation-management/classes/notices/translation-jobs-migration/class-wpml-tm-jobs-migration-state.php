<?php

class WPML_TM_Jobs_Migration_State {
	const MIGRATION_DONE_KEY         = 'wpml-tm-translation-jobs-migration';
	const FIXING_MIGRATION_STATE_KEY = 'wpml-tm-all-translation-jobs-migration';
	const MIGRATION_SKIPPED          = 'wpml-tm-translation-jobs-migration-skipped';

	/**
	 * The fixing migration has already been run but it contained errors
	 */
	const FIRST_MIGRATION_FIX_HAS_RUN = 1;
	/**
	 * We've already cleaned logs of the first fixing migration and are ready to run another this time flawless version
	 */
	const READY_TO_RUN_SECOND_MIGRATION_FIX = 2;
	/**
	 * The final flawless fixing migration has been run
	 */
	const SECOND_MIGRATION_FIX_HAS_RUN = 3;

	/**
	 * Checks if the original migration has been finished
	 *
	 * @return bool
	 */
	public function is_migrated() {
		return (bool) get_option( self::MIGRATION_DONE_KEY );
	}

	/**
	 * Checks if the fixing migration ( migration which fixes the flaws of the original migration ) has been run
	 *
	 * @return bool
	 */
	public function is_fixing_migration_done() {
		$option = (int) get_option( self::FIXING_MIGRATION_STATE_KEY );
		if ( $option === self::FIRST_MIGRATION_FIX_HAS_RUN ) { // clear previous log
			update_option( WPML_Translation_Jobs_Migration::MIGRATION_FIX_LOG_KEY, array(), false );
			update_option( self::FIXING_MIGRATION_STATE_KEY, self::READY_TO_RUN_SECOND_MIGRATION_FIX, true );
		}

		return (int) get_option( self::FIXING_MIGRATION_STATE_KEY ) === self::SECOND_MIGRATION_FIX_HAS_RUN;
	}

	public function mark_migration_as_done() {
		update_option( self::MIGRATION_DONE_KEY, 1, true );

		// a user has never run the original migration so it does not need the fixing migration
		$this->mark_fixing_migration_as_done();
	}

	public function mark_fixing_migration_as_done() {
		update_option( self::FIXING_MIGRATION_STATE_KEY, self::SECOND_MIGRATION_FIX_HAS_RUN, true );
	}

	/**
	 * @param bool $flag
	 */
	public function skip_migration( $flag = true ) {
		update_option( self::MIGRATION_SKIPPED, $flag, true );
	}

	/**
	 * @return bool
	 */
	public function is_skipped() {
		return (bool) get_option( self::MIGRATION_SKIPPED );
	}
}