<?php
/**
 * @author OnTheGo Systems
 */

namespace WPML\ST\Container;

class Config {

	static public function getSharedClasses() {
		return [
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
		];
	}

	static public function getAliases() {
		return [
			\WPML_ST_Translations_File_Dictionary_Storage::class => \WPML_ST_Translations_File_Dictionary_Storage_Table::class,
		];
	}
}
