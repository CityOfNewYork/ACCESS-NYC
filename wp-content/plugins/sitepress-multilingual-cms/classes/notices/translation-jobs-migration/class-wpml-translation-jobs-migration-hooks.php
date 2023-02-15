<?php

class WPML_Translation_Jobs_Migration_Hooks {

	private $notice;
	private $ajax_handler;

	/** @var WPML_Translation_Jobs_Migration_Repository */
	private $jobs_migration_repository;

	/** @var WPML_Upgrade_Schema $schema */
	private $schema;

	/** @var WPML_TM_Jobs_Migration_State */
	private $migration_state;

	public function __construct(
		WPML_Translation_Jobs_Migration_Notice $notice,
		$ajax_handler,
		WPML_Translation_Jobs_Migration_Repository $jobs_migration_repository,
		WPML_Upgrade_Schema $schema,
		WPML_TM_Jobs_Migration_State $migration_state
	) {
		$this->notice                    = $notice;
		$this->ajax_handler              = $ajax_handler;
		$this->jobs_migration_repository = $jobs_migration_repository;
		$this->schema                    = $schema;
		$this->migration_state           = $migration_state;
	}

	public function add_hooks() {
		add_action( 'init', array( $this, 'add_hooks_on_init' ), PHP_INT_MAX );
	}

	public function add_hooks_on_init() {
		if ( $this->new_columns_are_not_added_yet() ) {
			add_action( 'wpml_tm_lock_ui', array( $this, 'lock_tm_ui' ) );
		} elseif ( $this->needs_migration() ) {
			$this->notice->add_notice();

			add_action( 'wpml_tm_lock_ui', array( $this, 'lock_tm_ui' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action(
				'wp_ajax_' . WPML_Translation_Jobs_Migration_Ajax::ACTION,
				array( $this->ajax_handler, 'run_migration' )
			);
		}
	}

	/**
	 * @see
	 * `WPML_TM_Add_TP_Revision_And_TS_Status_Columns_To_Core_Status`
	 * `WPML_TM_Add_TP_Revision_And_TS_Status_Columns_To_Translation_Status`
	 * `WPML_TM_Add_TP_ID_Column_To_Translation_Status`
	 *
	 * @return bool
	 */
	private function new_columns_are_not_added_yet() {
		$has_columns = $this->schema->does_column_exist( 'icl_core_status', 'tp_revision' )
			&& $this->schema->does_column_exist( 'icl_core_status', 'ts_status' )
			&& $this->schema->does_column_exist( 'icl_translation_status', 'tp_revision' )
			&& $this->schema->does_column_exist( 'icl_translation_status', 'ts_status' )
			&& $this->schema->does_column_exist( 'icl_translation_status', 'tp_id' );

		return ! $has_columns;
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			'wpml-tm-translation-jobs-migration',
			WPML_TM_URL . '/dist/js/translationJobsMigration/app.js',
			array(),
			WPML_TM_VERSION
		);
	}

	/**
	 * @return bool
	 */
	private function needs_migration() {
		if ( $this->jobs_migration_repository->get_count() ) {
			return ! $this->skip_migration_if_service_is_not_active();
		}

		$this->migration_state->mark_migration_as_done();

		return false;
	}

	/**
	 * @return bool
	 */
	private function skip_migration_if_service_is_not_active() {
		if ( ! TranslationProxy::is_current_service_active_and_authenticated() ) {
			$this->migration_state->skip_migration( true );

			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function lock_tm_ui() {
		return true;
	}
}
