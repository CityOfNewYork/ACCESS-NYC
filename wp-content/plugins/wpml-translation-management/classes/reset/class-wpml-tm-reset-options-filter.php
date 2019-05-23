<?php
/**
 * WPML_TM_Reset_Options_Filter class file
 *
 * @package WPML\TM
 */

/**
 * Class WPML_TM_Reset_Options_Filter
 */
class WPML_TM_Reset_Options_Filter implements IWPML_Action {

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		add_filter( 'wpml_reset_options', array( $this, 'reset_options' ) );
	}

	/**
	 * Add options to reset.
	 *
	 * @param array $options Options.
	 *
	 * @return array
	 */
	public function reset_options( array $options ) {
		$options[] = WPML_TM_ATE_Job_Records::WPML_TM_ATE_JOB_RECORDS;
		$options[] = WPML_TM_ATE_Authentication::AMS_DATA_KEY;
		$options[] = WPML_TM_All_Admins_To_Translation_Managers::HAS_RUN_OPTION;
		$options[] = WPML_TM_Wizard_Options::WIZARD_COMPLETE_FOR_MANAGER;
		$options[] = WPML_TM_Wizard_Options::WIZARD_COMPLETE_FOR_ADMIN;
		$options[] = WPML_TM_Wizard_Options::CURRENT_STEP;
		$options[] = WPML_TM_Wizard_Options::WHO_WILL_TRANSLATE_MODE;

		return $options;
	}
}
