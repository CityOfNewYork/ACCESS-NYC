<?php

namespace WPML\ST;

class Actions {

	public static function get() {
		return array(
			'WPML_ST_Theme_Plugin_Localization_Resources_Factory',
			'WPML_ST_Theme_Plugin_Localization_Options_UI_Factory',
			'WPML_ST_Theme_Plugin_Localization_Options_Settings_Factory',
			'WPML_ST_Theme_Plugin_Scan_Dir_Ajax_Factory',
			'WPML_ST_Theme_Plugin_Scan_Files_Ajax_Factory',
			'WPML_ST_Update_File_Hash_Ajax_Factory',
			'WPML_ST_Theme_Plugin_Hooks_Factory',
			'WPML_ST_Taxonomy_Labels_Translation_Factory',
			'WPML_ST_String_Translation_AJAX_Hooks_Factory',
			'WPML_ST_Remote_String_Translation_Factory',
			'WPML_ST_Privacy_Content_Factory',
			'WPML_ST_String_Tracking_AJAX_Factory',
			\WPML_ST_Translation_Memory::class,
			'WPML_ST_Script_Translations_Hooks_Factory',
			\WPML\ST\MO\Scan\UI\Factory::class,
			'WPML\ST\Rest\FactoryLoader',
			\WPML\ST\Gettext\HooksFactory::class,
			\WPML_ST_Support_Info_Filter::class,
			\WPML\ST\Troubleshooting\BackendHooks::class,
			\WPML\ST\Troubleshooting\AjaxFactory::class,
			\WPML\ST\MO\File\FailureHooksFactory::class,
			\WPML\ST\DB\Mappers\Hooks::class,
			\WPML\ST\Shortcode\Hooks::class,
			\WPML\ST\AdminTexts\UI::class,
			\WPML\ST\PackageTranslation\Hooks::class,
			\WPML\ST\Main\UI::class,
			\WPML\ST\StringsCleanup\UI::class,
			\WPML\ST\DisplayAsTranslated\CheckRedirect::class,
		);
	}
}
