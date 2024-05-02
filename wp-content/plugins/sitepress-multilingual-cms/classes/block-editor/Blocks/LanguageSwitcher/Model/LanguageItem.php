<?php
namespace WPML\BlockEditor\Blocks\LanguageSwitcher\Model;

class LanguageItem {

	/** @var string */
	private $displayName;

	/** @var string */
	private $nativeName;

	/** @var string */
	private $code;

	/** @var string */
	private $url;

	/** @var string */
	private $flagUrl;

	/** @var string */
	private $flagTitle;

	/** @var string */
	private $flagAlt;

	/**
	 * @param string $displayName
	 * @param string $nativeName
	 * @param string $code
	 * @param string $url
	 * @param string $flagUrl
	 * @param string $flagTitle
	 * @param string $flagAlt
	 */
	public function __construct( $displayName, $nativeName, $code, $url, $flagUrl, $flagTitle, $flagAlt ) {
		$this->displayName = $displayName;
		$this->nativeName = $nativeName;
		$this->code = $code;
		$this->url = $url;
		$this->flagUrl = $flagUrl;
		$this->flagTitle = $flagTitle;
		$this->flagAlt = $flagAlt;
	}

	/**
	 * @return string
	 */
	public function getDisplayName() {
		return $this->displayName;
	}

	/**
	 * @return string
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getFlagUrl() {
		return $this->flagUrl;
	}

	/**
	 * @return string
	 */
	public function getNativeName() {
		return $this->nativeName;
	}

	/**
	 * @return string
	 */
	public function getFlagTitle() {
		return $this->flagTitle;
	}

	/**
	 * @return string
	 */
	public function getFlagAlt() {
		return $this->flagAlt;
	}

}