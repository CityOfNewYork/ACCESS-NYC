<?php

use function WPML\Container\make;
use WPML\TM\ATE\ReturnedJobsQueue;

/**
 * Factory class for \WPML_TM_ATE_Jobs_Actions.
 *
 * @package wpml\tm
 *
 * @author  OnTheGo Systems
 */
class WPML_TM_ATE_Jobs_Actions_Factory implements IWPML_Backend_Action_Loader, \IWPML_REST_Action_Loader {
	/**
	 * The instance of \WPML_Current_Screen.
	 *
	 * @var WPML_Current_Screen
	 */
	private $current_screen;

	/**
	 * It returns an instance of \WPML_TM_ATE_Jobs_Actions or null if ATE is not enabled and active.
	 *
	 * @return \WPML_TM_ATE_Jobs_Actions|null
	 * @throws \Auryn\InjectionException
	 */
	public function create() {
		$ams_ate_factories = wpml_tm_ams_ate_factories();

		if ( WPML_TM_ATE_Status::is_enabled_and_activated() ) {
			$sitepress      = $this->get_sitepress();
			$current_screen = $this->get_current_screen();

			$ate_api  = $ams_ate_factories->get_ate_api();
			$records  = wpml_tm_get_ate_job_records();
			$ate_jobs = new WPML_TM_ATE_Jobs( $records );

			$translator_activation_records = new WPML_TM_AMS_Translator_Activation_Records( new WPML_WP_User_Factory() );

			return new WPML_TM_ATE_Jobs_Actions(
				$ate_api,
				$ate_jobs,
				$sitepress,
				$current_screen,
				$translator_activation_records
			);
		}

		return null;
	}

	/**
	 * The global instance of \Sitepress.
	 *
	 * @return SitePress
	 */
	private function get_sitepress() {
		global $sitepress;

		return $sitepress;
	}

	/**
	 * It gets the instance of \WPML_Current_Screen.
	 *
	 * @return \WPML_Current_Screen
	 */
	private function get_current_screen() {
		if ( ! $this->current_screen ) {
			$this->current_screen = new WPML_Current_Screen();
		}

		return $this->current_screen;
	}
}
