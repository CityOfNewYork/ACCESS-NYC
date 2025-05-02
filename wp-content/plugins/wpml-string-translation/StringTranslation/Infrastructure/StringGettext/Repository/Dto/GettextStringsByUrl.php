<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Repository\Dto;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;

class GettextStringsByUrl {

	/** @var StringItem[] */
	private $strings;

	/** string $requestUrl */
	private $requestUrl;

	/**
	 * @param StringItem[] $strings
	 * @param string       $requestUrl
	 */
	public function __construct(
		array  $strings,
		string $requestUrl
	) {
		$this->strings    = $strings;
		$this->requestUrl = $requestUrl;
	}

	/**
	 * @return StringItem[]
	 */
	public function getStrings(): array {
		return $this->strings;
	}

	public function getRequestUrl(): string {
		return $this->requestUrl;
	}
}