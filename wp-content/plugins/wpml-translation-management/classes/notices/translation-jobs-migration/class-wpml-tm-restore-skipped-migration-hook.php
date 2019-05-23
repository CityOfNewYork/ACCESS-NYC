<?php

/**
 * The class adds the hook which is triggered in the moment of Translation Service authorization.
 * It checks if the migration has been skipped due to lack of activated service and if so, it turns on the migration.
 */
class WPML_TM_Restore_Skipped_Migration implements IWPML_Action {
	/** @var WPML_TM_Jobs_Migration_State */
	private $migration_state;

	/**
	 * @param WPML_TM_Jobs_Migration_State $migration_state
	 */
	public function __construct( WPML_TM_Jobs_Migration_State $migration_state ) {
		$this->migration_state = $migration_state;
	}


	public function add_hooks() {
		add_action( 'wpml_tm_translation_service_authorized', array( $this, 'restore' ) );
	}

	public function restore() {
		if ( $this->migration_state->is_skipped() ) {
			$this->migration_state->skip_migration( false );
		}
	}
}