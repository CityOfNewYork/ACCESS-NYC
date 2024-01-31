<?php
namespace WPML\BlockEditor\Blocks\LanguageSwitcher\Model\Label;

use WPML\BlockEditor\Blocks\LanguageSwitcher\Model\LanguageItem;

interface LabelTemplateInterface {
	/**
	 * @param \DOMXPath $domXPath
	 * @param $prefix
	 * @return boolean
	 */
	public function matchesXPath( \DOMXPath $domXPath, $prefix );

	/**
	 * @param LanguageItem $languageItem
	 * @return string
	 */
	public function getDisplayName( LanguageItem $languageItem );
}
