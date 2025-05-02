<?php

namespace WPML\StringTranslation\Application\StringCore\Query\Dto;

class FiltersDto {

	/** @var string[] */
	private $domains;

	/** @var string[] */
	private $translationPriorities;

	public function __construct(
		array $domains,
		array $translationPriorities
	) {
		$this->domains               = $domains;
		$this->translationPriorities = $translationPriorities;
	}

	public function getDomains(): array {
		return $this->domains;
	}

	public function getTranslationPriorities(): array {
		return $this->translationPriorities;
	}
}