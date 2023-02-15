<?php
/**
 * @author OnTheGo Systems
 */

namespace WPML\ST\Container;

class Config {

	public static function getSharedClasses() {
		return [
			\WPML\ST\StringsCleanup\UntranslatedStrings::class,
			\WPML\ST\Gettext\AutoRegisterSettings::class,
			\WPML\ST\Gettext\Hooks::class,
			\WPML\ST\Gettext\Settings::class,
			\WPML\ST\MO\LoadedMODictionary::class,
			\WPML\ST\MO\File\Manager::class,
			\WPML\ST\MO\File\Builder::class,
			\WPML\ST\Package\Domains::class,
			\WPML\ST\StringsFilter\Provider::class,
			\WPML\ST\TranslationFile\Domains::class,
			\WPML_String_Translation::class,
			\WPML_ST_Blog_Name_And_Description_Hooks::class,
			\WPML_ST_Settings::class,
			\WPML_ST_String_Factory::class,
			\WPML_ST_Upgrade::class,
			\WPML_Theme_Localization_Type::class,
			\WPML_ST_Translations_File_Dictionary_Storage_Table::class,
			\WPML\ST\TranslationFile\Sync\TranslationUpdates::class,
		];
	}

	public static function getAliases() {
		return [
			\WPML_ST_Translations_File_Dictionary_Storage::class => \WPML_ST_Translations_File_Dictionary_Storage_Table::class,
		];
	}

	public static function getDelegated() {
		return [
			\WPML_Admin_Texts::class => function() {
				return wpml_st_load_admin_texts(); },
		];
	}

}
