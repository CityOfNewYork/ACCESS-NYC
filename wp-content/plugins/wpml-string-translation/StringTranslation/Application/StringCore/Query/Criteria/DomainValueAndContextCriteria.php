<?php

namespace WPML\StringTranslation\Application\StringCore\Query\Criteria;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;

class DomainValueAndContextCriteria {

	/** @var StringItem[] */
	private $stringsToSearch;

	/** @var string[]  */
	private $fieldsToHydrate;

	public function __construct(
		array $stringsToSearch,
		array $fieldsToHydrate
	) {
		$this->stringsToSearch = $stringsToSearch;
		$this->fieldsToHydrate = $fieldsToHydrate;
	}

	public function getStringsToSearch(): array {
		return $this->stringsToSearch;
	}

	public function getFieldsToHydrate(): array {
		return $this->fieldsToHydrate;
	}
}