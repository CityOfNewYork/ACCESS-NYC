<?php

namespace WPML\StringTranslation\Application\StringCore\Domain;

class StringTranslation {

	/** @var StringItem|null */
	private $string;

	/** @var string */
	private $language;

	/** @var string */
	private $value;

	/**
	 * @param StringItem|null $string
	 * @param string          $language
	 * @param string          $value
	 */
	public function __construct(
		StringItem $string = null,
		string     $language,
		string     $value
	) {
		$this->setString( $string );
		$this->setLanguage( $language );
		$this->setValue( $value );
	}

	public function setString( StringItem $string ) {
		$this->string = $string;
	}

	public function getString(): StringItem {
		return $this->string;
	}

	public function getLanguage(): string {
		return $this->language;
	}

	public function setLanguage( string $language ) {
		$this->language = $language;
	}

	public function getValue(): string {
		return $this->value;
	}

	public function setValue( string $value ) {
		$this->value = $value;
	}
}