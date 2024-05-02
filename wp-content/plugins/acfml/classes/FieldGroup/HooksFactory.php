<?php

namespace ACFML\FieldGroup;

class HooksFactory implements \IWPML_Backend_Action_Loader {

	/**
	 * @return \IWPML_Action[]
	 */
	public function create() {
		/**
		 * @var \SitePress $sitepress
		 */
		global $sitepress;

		$fieldNamePatterns = new FieldNamePatterns();

		return [
			new UIHooks(),
			new SaveHooks( $fieldNamePatterns, new DetectNonTranslatableLocations() ),
			new CptLockHooks( $sitepress, wpml_load_core_tm() ),
			new SettingsLockHooks( $fieldNamePatterns ),
			new TranslationModeColumnHooks(),
			new TranslationEditorHooks(),
		];
	}
}
