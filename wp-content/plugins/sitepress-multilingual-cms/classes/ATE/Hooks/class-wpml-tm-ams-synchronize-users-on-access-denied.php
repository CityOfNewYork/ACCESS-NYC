<?php

class WPML_TM_AMS_Synchronize_Users_On_Access_Denied {

	const ERROR_MESSAGE = 'Authentication error, please contact your translation manager to check your subscription';

	/** @var WPML_TM_AMS_Synchronize_Actions */
	private $ams_synchronize_actions;

	/** @var WPML_TM_ATE_Jobs */
	private $ate_jobs;

	public function add_hooks() {
		if ( WPML_TM_ATE_Status::is_enabled_and_activated() ) {
			add_action( 'init', array( $this, 'catch_access_error' ) );
		}
	}

	public function catch_access_error() {
		if ( ! $this->ate_redirected_due_to_lack_of_access() ) {
			return;
		}

		$this->get_ams_synchronize_actions()->synchronize_translators();

		if ( ! isset( $_GET['ate_job_id'] ) ) {
			return;
		}

		$wpml_job_id = $this->get_ate_jobs()->get_wpml_job_id( $_GET['ate_job_id'] );
		if ( ! $wpml_job_id ) {
			return;
		}

		$url = admin_url(
			'admin.php?page='
			. WPML_TM_FOLDER
			. '/menu/translations-queue.php&job_id='
			. $wpml_job_id
		);

		wp_safe_redirect( $url, 302, 'WPML' );
	}

	/**
	 * @return bool
	 */
	private function ate_redirected_due_to_lack_of_access() {
		return isset( $_GET['message'] ) && false !== strpos( $_GET['message'], self::ERROR_MESSAGE );
	}

	/**
	 * @return IWPML_Action|IWPML_Action[]|WPML_TM_AMS_Synchronize_Actions
	 */
	private function get_ams_synchronize_actions() {
		if ( ! $this->ams_synchronize_actions ) {
			$factory                       = new WPML_TM_AMS_Synchronize_Actions_Factory();
			$this->ams_synchronize_actions = $factory->create();
		}

		return $this->ams_synchronize_actions;
	}

	/**
	 * @return WPML_TM_ATE_Jobs
	 */
	private function get_ate_jobs() {
		if ( ! $this->ate_jobs ) {
			$ate_jobs_records = wpml_tm_get_ate_job_records();
			$this->ate_jobs   = new WPML_TM_ATE_Jobs( $ate_jobs_records );
		}

		return $this->ate_jobs;
	}

	/**
	 * @param WPML_TM_AMS_Synchronize_Actions $ams_synchronize_actions
	 */
	public function set_ams_synchronize_actions( WPML_TM_AMS_Synchronize_Actions $ams_synchronize_actions ) {
		$this->ams_synchronize_actions = $ams_synchronize_actions;
	}

	/**
	 * @param WPML_TM_ATE_Jobs $ate_jobs
	 */
	public function set_ate_jobs( WPML_TM_ATE_Jobs $ate_jobs ) {
		$this->ate_jobs = $ate_jobs;
	}
}
