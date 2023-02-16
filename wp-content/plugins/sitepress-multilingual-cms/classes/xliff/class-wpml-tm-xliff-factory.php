<?php

/**
 * @author OnTheGo Systems
 */
class WPML_TM_XLIFF_Factory {
	const WPML_XLIFF_DEFAULT_VERSION = WPML_XLIFF_DEFAULT_VERSION;
	const CREATE_FOR_WRITE           = 'WPML_TM_Xliff_Writer';
	const CREATE_FOR_FRONT_END       = 'WPML_TM_Xliff_Frontend';

	public function create_writer( $xliff_version = self::WPML_XLIFF_DEFAULT_VERSION ) {
		return new WPML_TM_Xliff_Writer( wpml_tm_load_job_factory(), $xliff_version, wpml_tm_xliff_shortcodes() );
	}

	public function create_frontend() {
		global $sitepress;
		$support_info = new WPML_TM_Support_Info();

		return new WPML_TM_Xliff_Frontend( wpml_tm_load_job_factory(), $sitepress, $support_info->is_simplexml_extension_loaded() );
	}
}
