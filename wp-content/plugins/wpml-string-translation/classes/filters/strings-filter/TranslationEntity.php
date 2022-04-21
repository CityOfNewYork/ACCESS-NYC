<?php

namespace WPML\ST\StringsFilter;

class TranslationEntity {
	/** @var string */
	private $value;

	/** @var bool */
	private $hasTranslation;

	/** @var bool */
	private $stringRegistered;

	/**
	 * @param string $value
	 * @param bool   $hasTranslation
	 * @param bool   $stringRegistered
	 */
	public function __construct( $value, $hasTranslation, $stringRegistered = true ) {
		$this->value            = $value;
		$this->hasTranslation   = $hasTranslation;
		$this->stringRegistered = $stringRegistered;
	}

	/**
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @return bool
	 */
	public function isStringRegistered() {
		return $this->stringRegistered;
	}

	/**
	 * @return bool
	 */
	public function hasTranslation() {
		return $this->hasTranslation;
	}
}
