<?php
/**
 * WPML_TM_Reset_Options_Filter_Factory class file.
 *
 * @package WPML\TM
 */

/**
 * Class WPML_TM_Reset_Options_Filter_Factory
 */
class WPML_TM_Reset_Options_Filter_Factory implements IWPML_Backend_Action_Loader {

	/**
	 * Create reset options filter.
	 *
	 * @return WPML_TM_Reset_Options_Filter
	 */
	public function create() {

		return new WPML_TM_Reset_Options_Filter();
	}
}
