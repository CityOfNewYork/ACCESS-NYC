<?php
namespace WPML\BlockEditor\Blocks\LanguageSwitcher\Model;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Relation;
use function WPML\FP\invoke;
use function WPML\FP\pipe;

class LanguageSwitcher {

	/** @var LanguageItem[] */
	private $languageItems;

	/** @var string */
	private $currentLanguageCode;

	/**
	 * @param string $currentLanguageItem
	 * @param LanguageItem[] $languageItems
	 */
	public function __construct( $currentLanguageCode, array $languageItems )
	{
		$this->currentLanguageCode = $currentLanguageCode;
		$this->languageItems = $languageItems;
	}

	/**
	 * @return LanguageItem[]
	 */
	public function getLanguageItems() {
		return $this->languageItems;
	}

	/**
	 * @return null|LanguageItem
	 */
	public function getCurrentLanguageItem() {
		return Lst::find(pipe(invoke('getCode'), Relation::equals($this->currentLanguageCode)), $this->languageItems);
	}

	/**
	 * @return LanguageItem[]
	 */
	public function getAlternativeLanguageItems() {
		return Fns::reject(pipe(invoke('getCode'), Relation::equals($this->currentLanguageCode)), $this->languageItems);
	}

	/**
	 * @return string
	 */
	public function getCurrentLanguageCode() {
		return $this->currentLanguageCode;
	}
}