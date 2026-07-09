<?php

namespace ACFML\Container;

class Config {

	/**
	 * @return string[]
	 */
	public static function getSharedClasses() {
		return [
			\ACFML\FieldPreferences\TranslationJobs::class,
			\ACFML\FieldGroup\FieldNamePatterns::class,
			\ACFML\Field\Resolver::class,
			\ACFML\FieldReferenceAdjuster::class,
			\ACFML\MigrateBlockPreferences::class,
			\ACFML\Tools\Export::class,
			\ACFML\Tools\Import::class,
			\ACFML\Tools\Local::class,
			\WPML_ACF_Attachments::class,
			\WPML_ACF_Blocks::class,
			\WPML_ACF_Custom_Fields_Sync::class,
			\WPML_ACF_Display_Translated::class,
			\WPML_ACF_Duplicated_Post::class,
			\WPML_ACF_Editor_Hooks::class,
			\WPML_ACF_Field_Annotations::class,
			\WPML_ACF_Field_Groups::class,
			\WPML_ACF_Field_Settings::class,
			\WPML_ACF_Location_Rules::class,
			\WPML_ACF_Migrate_Option_Page_Strings::class,
			\WPML_ACF_Options_Page::class,
			\WPML_ACF_Pro::class,
			\WPML_ACF_Repeater_Shuffle::class,
			\WPML_ACF_Translatable_Groups_Checker::class,
			\WPML_ACF_Worker::class,
			\WPML_ACF_Xliff::class,
		];
	}
}
