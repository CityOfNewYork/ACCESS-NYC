<?php

class WPML_Compatibility_Plugin_Fusion_Hooks_Factory implements IWPML_Frontend_Action_Loader, IWPML_Backend_Action_Loader {

	public function create() {
		global $sitepress;

		$activeLanguages   = $this->get_filtered_active_languages();
		$postStatusDisplay = new WPML_Post_Status_Display( $activeLanguages );
		unset( $activeLanguages[ $sitepress->get_current_language() ] );

		return new WPML_Compatibility_Plugin_Fusion_Global_Element_Hooks(
			$sitepress,
			new WPML_Translation_Element_Factory( $sitepress ),
			new WPML_Custom_Columns( $sitepress ),
			$activeLanguages,
			$postStatusDisplay
		);
	}

	/**
	 * Get list of active languages.
	 *
	 * @return array
	 */
	private function get_filtered_active_languages() {
		global $sitepress;

		$activeLanguages = $sitepress->get_active_languages();

		return apply_filters( 'wpml_active_languages_access', $activeLanguages, [ 'action' => 'edit' ] );
	}
}
