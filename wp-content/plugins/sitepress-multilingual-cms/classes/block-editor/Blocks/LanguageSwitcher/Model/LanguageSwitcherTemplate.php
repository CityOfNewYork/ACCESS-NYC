<?php
namespace WPML\BlockEditor\Blocks\LanguageSwitcher\Model;

class LanguageSwitcherTemplate {

	/** @var LanguageItemTemplate */
	private $languageItemTemplate;

	/** @var LanguageItemTemplate */
	private $currentLanguageItemTemplate;

	/** @var \DOMDocument */
	private $DOMDocument;

	/** @var \DOMXpath */
	private $DOMXpath;

	/**
	 * @param LanguageItemTemplate $languageItemTemplate
	 * @param LanguageItemTemplate $currentLanguageItemTemplate
	 * @param \DOMDocument $DOMDocument
	 */
	public function __construct(
		LanguageItemTemplate $languageItemTemplate,
		LanguageItemTemplate $currentLanguageItemTemplate,
		\DOMDocument $DOMDocument
	) {
		$this->languageItemTemplate = $languageItemTemplate;
		$this->currentLanguageItemTemplate = $currentLanguageItemTemplate;
		$this->DOMDocument = $DOMDocument;
		$this->DOMXpath = new \DOMXPath( $DOMDocument );
	}

	/**
	 * @return LanguageItemTemplate
	 */
	public function getLanguageItemTemplate() {
		return $this->languageItemTemplate;
	}

	/**
	 * @return LanguageItemTemplate
	 */
	public function getCurrentLanguageItemTemplate() {
		return $this->currentLanguageItemTemplate;
	}

	/**
	 * @return \DOMXPath
	 */
	public function getDOMXPath() {
		return $this->DOMXpath;
	}

	/**
	 * @return \DOMDocument
	 */
	public function getDOMDocument() {
		return $this->DOMDocument;
	}
}