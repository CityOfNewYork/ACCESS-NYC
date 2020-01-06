<?php

use function WPML\Container\make;
use WPML\ST\TranslationFile\UpdateHooks;

class WPML_ST_Script_Translations_Hooks_Factory implements IWPML_Backend_Action_Loader, IWPML_Frontend_Action_Loader {

	/**
	 * Create hooks.
	 *
	 * @return array|IWPML_Action
	 * @throws \Auryn\InjectionException Auryn Exception.
	 */
	public function create() {
		$hooks = array();

		$jed_file_manager = make(
			WPML_ST_JED_File_Manager::class,
			[ ':builder' => make( WPML_ST_JED_File_Builder::class ) ]
		);

		$hooks['update'] = $this->get_update_hooks( $jed_file_manager );

		if ( ! wpml_is_ajax() && ! wpml_is_rest_request() ) {
			$hooks['filtering'] = $this->get_filtering_hooks( $jed_file_manager );
		}

		return $hooks;
	}

	/**
	 * @param WPML_ST_JED_File_Manager $jed_file_manager
	 *
	 * @return UpdateHooks
	 */
	private function get_update_hooks( $jed_file_manager ) {
		return make(
			UpdateHooks::class,
			[ ':file_manager' => $jed_file_manager ]
		);
	}

	/**
	 * @param WPML_ST_JED_File_Manager $jed_file_manager
	 *
	 * @return WPML_ST_Script_Translations_Hooks
	 */
	private function get_filtering_hooks( $jed_file_manager ) {
		return make(
			WPML_ST_Script_Translations_Hooks::class,
			[ ':jed_file_manager' => $jed_file_manager ]
		);
	}
}
