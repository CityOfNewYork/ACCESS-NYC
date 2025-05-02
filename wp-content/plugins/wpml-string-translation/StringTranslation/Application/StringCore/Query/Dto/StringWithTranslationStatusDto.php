<?php

namespace WPML\StringTranslation\Application\StringCore\Query\Dto;

use WPML\StringTranslation\Application\Translation\Query\Dto\TranslationStatusDto;

class StringWithTranslationStatusDto extends StringDto {

	/** @var array<string, TranslationStatusDto> */
	private $translationStatuses;

	/**
	 * @param int $id
	 * @param string $language
	 * @param string $domain
	 * @param string $context
	 * @param string $name
	 * @param string $value
	 * @param int $status
	 * @param string $translationPriority
	 * @param int $wordCount
	 * @param int $kind
 	 * @param int $type
	 * @param array $sources
	 * @param array<string, TranslationStatusDto> $translationStatuses
	 */
	public function __construct(
		int $id,
		string $language,
		string $domain,
		string $context,
		string $name,
		string $value,
		int $status,
		string $translationPriority,
		int $wordCount,
		int $kind,
		int $type,
		array $sources = [],
		array $translationStatuses = []
	) {
		parent::__construct(
			$id,
			$language,
			$domain,
			$context,
			$name,
			$value,
			$status,
			$translationPriority,
			$wordCount,
			$kind,
			$type,
			$sources
		);

		$this->translationStatuses = $translationStatuses;
	}

	/** @return array<string, TranslationStatusDto> */
	public function getTranslationStatuses(): array {
		return $this->translationStatuses;
	}

}