<?php

class WPML_TM_Upgrade_ATE_Jobs_Cleanup implements IWPML_Upgrade_Command {

	/**
	 * Runs the upgrade process.
	 *
	 * @return bool
	 */
	public function run() {
		wpml_tm_get_ate_job_records()->cleanup_data();
		return true;
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
