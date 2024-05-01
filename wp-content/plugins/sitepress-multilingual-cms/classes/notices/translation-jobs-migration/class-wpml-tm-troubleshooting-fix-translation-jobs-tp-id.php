<?php

class WPML_TM_Troubleshooting_Fix_Translation_Jobs_TP_ID {

	const AJAX_ACTION = 'wpml-fix-translation-jobs-tp-id';

	private $jobs_migration;
	private $jobs_repository;

	public function __construct( WPML_Translation_Jobs_Migration $jobs_migration, WPML_TM_Jobs_Repository $jobs_repository ) {
		$this->jobs_migration  = $jobs_migration;
		$this->jobs_repository = $jobs_repository;
	}

	public function add_hooks() {
		add_action(
			'wpml_troubleshooting_after_fix_element_type_collation',
			array(
				$this,
				'render_troubleshooting_section',
			)
		);
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'fix_tp_id_ajax' ) );

	}

	public function fix_tp_id_ajax() {
		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], self::AJAX_ACTION ) ) {
			$job_ids = isset( $_POST['job_ids'] ) ? array_map( 'intval', explode( ',', filter_var( $_POST['job_ids'], FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) ) : array();
			$jobs    = array();

			foreach ( $job_ids as $job_id ) {
				if ( ! $job_id ) {
					continue;
				}

				$params = new WPML_TM_Jobs_Search_Params();
				$params->set_scope( WPML_TM_Jobs_Search_Params::SCOPE_REMOTE );
				$params->set_job_types( array( WPML_TM_Job_Entity::POST_TYPE, WPML_TM_Job_Entity::PACKAGE_TYPE ) );
				$params->set_local_job_id( $job_id );

				$jobs[] = current( $this->jobs_repository->get( $params )->getIterator()->getArrayCopy() );
			}

			if ( $jobs ) {
				$this->jobs_migration->migrate_jobs( $jobs, true );
			}

			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	public function enqueue_scripts( $hook ) {
		if ( WPML_PLUGIN_FOLDER . '/menu/troubleshooting.php' === $hook ) {
			wp_enqueue_script( 'wpml-fix-tp-id', WPML_TM_URL . '/res/js/fix-tp-id.js', array( 'jquery' ), ICL_SITEPRESS_VERSION );
		}
	}


	public function render_troubleshooting_section() {
		?>
		<p>
			<input id="wpml_fix_tp_id_text" type="text" value=""/><input id="wpml_fix_tp_id_btn" type="button" class="button-secondary" value="<?php esc_attr_e( 'Fix WPML Translation Jobs "tp_id" field', 'sitepress' ); ?>"/><br/>
			<?php wp_nonce_field( self::AJAX_ACTION, 'wpml-fix-tp-id-nonce' ); ?>
			<small style="margin-left:10px;"><?php esc_attr_e( 'Fixes the "tp_id" field of WPML ranslation jobs and set the status to "in progress" (it requires manual action to re-sync translation status + download translations). It accepts comma separated values of translation job IDs (rid).', 'sitepress' ); ?></small>
		</p>
		<?php

	}
}
