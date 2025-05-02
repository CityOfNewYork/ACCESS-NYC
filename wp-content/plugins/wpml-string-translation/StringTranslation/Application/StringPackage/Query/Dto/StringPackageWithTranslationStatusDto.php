<?php

namespace WPML\StringTranslation\Application\StringPackage\Query\Dto;

use WPML\StringTranslation\Application\Translation\Query\Dto\TranslationStatusDto;

class StringPackageWithTranslationStatusDto {

	/** @var int */
	private $id;

	/**
	 * Title of the package.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * `name` column from icl_string_packages.
	 * Usually the ID of object but could be hardcoded strings too
	 * e.g. `widget` for block widget package.
	 *
	 * @var string
	 */
	private $name;

	/** @var int */
	private $lastEdit;

	/** @var int */
	private $wordCount;

	/** @var string */
	private $type;

	/** @var array<string, TranslationStatusDto> */
	private $translationStatuses;

	/** @var string|null */
	private $translatorNote;

	/**
	 * @param int $id
	 * @param string $title
	 * @param string $name
	 * @param int $lastEdit
	 * @param string $type
	 * @param array<string, TranslationStatusDto> $translationStatuses
	 * @param int $wordCount
	 * @param string|null $translatorNote
	 */
	public function __construct(
		int    $id,
		string $title,
		string $name,
		int    $lastEdit,
		string $type,
		array  $translationStatuses,
		int    $wordCount,
		$translatorNote
	) {
		$this->id                  = $id;
		$this->type                = $type;
		$this->translationStatuses = $translationStatuses;
		$this->title               = $title;
		$this->name                = $name;
		$this->lastEdit            = $lastEdit;
		$this->wordCount           = $wordCount;
		$this->translatorNote      = $translatorNote;
	}

	public function getId(): int {
		return $this->id;
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getLastEdit(): int {
		return $this->lastEdit;
	}

	public function getType(): string {
		return $this->type;
	}

	/** @return array<string, TranslationStatusDto> */
	public function getTranslationStatuses(): array {
		return $this->translationStatuses;
	}

	public function getWordCount(): int {
		return $this->wordCount;
	}

	/**
	 * @return string|null
	 */
	public function getTranslatorNote() {
		return $this->translatorNote;
	}
}
