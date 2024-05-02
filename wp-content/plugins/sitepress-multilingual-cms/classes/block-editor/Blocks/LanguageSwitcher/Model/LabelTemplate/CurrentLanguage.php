<?php
namespace WPML\BlockEditor\Blocks\LanguageSwitcher\Model\Label;

use WPML\BlockEditor\Blocks\LanguageSwitcher\Model\LanguageItem;

class CurrentLanguage implements LabelTemplateInterface {
	const XPATH_LOCATOR    = '//*[@data-wpml-label-type="current"]';
	public function matchesXPath( \DOMXPath $domXPath, $prefix ) {
		return $domXPath->query( $prefix . self::XPATH_LOCATOR )->length > 0;
	}
	public function getDisplayName( LanguageItem $languageItem ) {
		return $languageItem->getDisplayName();
	}
}
