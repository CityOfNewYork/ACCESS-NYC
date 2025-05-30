<?php

use function WPML\Container\make;

class WPML_Cornerstone_Integration_Factory {

	const SLUG = 'cornerstone';

	public function create() {
		$action_filter_loader = new WPML_Action_Filter_Loader();
		$action_filter_loader->load(
			[
				'WPML_PB_Cornerstone_Handle_Custom_Fields_Factory',
				'WPML_Cornerstone_Media_Hooks_Factory',
				\WPML\PB\Cornerstone\Config\Factory::class,
				\WPML\PB\Cornerstone\Styles\Hooks::class,
				\WPML\PB\Cornerstone\Hooks\Editor::class,
				\WPML\PB\Cornerstone\Hooks\ShortcodeAttributes::class,
				\WPML\PB\Cornerstone\Hooks\Media::class,
				\WPML\PB\Cornerstone\Hooks\TranslationJobLabels::class,
				\WPML\PB\Cornerstone\Hooks\TranslationGuiLabels::class,
			]
		);

		$nodes         = new WPML_Cornerstone_Translatable_Nodes();
		$data_settings = new WPML_Cornerstone_Data_Settings();

		$string_registration_factory = new WPML_String_Registration_Factory( $data_settings->get_pb_name() );
		$string_registration         = $string_registration_factory->create();

		$factory  = make( WPML_PB_Factory::class );
		$strategy = make( WPML_PB_API_Hooks_Strategy::class, [ ':name' => $data_settings->get_pb_name() ] );
		$strategy->set_factory( $factory );

		$reuse_translation = make( WPML_PB_Reuse_Translations_By_Strategy::class, [ ':strategy' => $strategy ] );

		$register_strings   = new WPML_Cornerstone_Register_Strings( $nodes, $data_settings, $string_registration, $reuse_translation );
		$update_translation = new WPML_Cornerstone_Update_Translation( $nodes, $data_settings );

		return new WPML_Page_Builders_Integration( $register_strings, $update_translation, $data_settings );
	}
}
