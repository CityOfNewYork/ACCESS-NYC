<?php
namespace WPML\BlockEditor\Blocks\LanguageSwitcher;

use WPML\BlockEditor\Blocks\LanguageSwitcher\Model\LanguageItem;
use WPML\BlockEditor\Blocks\LanguageSwitcher\Model\LanguageSwitcher;
use WPML\FP\Obj;

class Repository {

	/** @var \WPML_LS_Model_Build */
	private $languageSwitcherModelBuilder;

	public function __construct(
		\SitePress $sitepress,
		\WPML_LS_Dependencies_Factory $dependencies = null
	) {
		$dependencies = $dependencies ?: new \WPML_LS_Dependencies_Factory( $sitepress, \WPML_Language_Switcher::parameters() );

		$this->languageSwitcherModelBuilder = new \WPML_LS_Model_Build( $dependencies->settings(), $sitepress, 'wpml-ls-' );
	}

	/**
	 * @return LanguageSwitcher
	 */
	public function getCurrentLanguageSwitcher( ) {
		$model = $this->languageSwitcherModelBuilder->get( new \WPML_LS_Slot( [
			'display_link_for_current_lang' => true,
			'display_names_in_native_lang' => true,
			'display_names_in_current_lang' => true,
			'display_flags' => true,
		]) );

		$languages = $model[ 'languages' ];
		$currentLanguageCode = $model[ 'current_language_code' ];
		
		$languageItems = [];
		foreach( $languages as $language ) {
			$languageItems[] = $this->buildLanguageItem( $language );
		}

		return new LanguageSwitcher( $currentLanguageCode, $languageItems );
	}

	private function buildLanguageItem( array $language ) {
		return new LanguageItem(
			Obj::propOr( '', 'display_name', $language ),
			Obj::propOr( '', 'native_name', $language ),
			Obj::propOr( '', 'code', $language ),
			Obj::propOr( '', 'url', $language ),
			Obj::propOr( '', 'flag_url', $language ),
			Obj::propOr( '', 'flag_title', $language ),
			Obj::propOr( '', 'flag_alt', $language )
		);
	}

}

