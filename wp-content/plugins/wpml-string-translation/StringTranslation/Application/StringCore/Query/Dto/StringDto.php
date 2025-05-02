<?php

namespace WPML\StringTranslation\Application\StringCore\Query\Dto;

use WPML\StringTranslation\Application\Translation\Query\Dto\TranslationStatusDto;

class StringDto {

	/** @var int */
	protected $id;

	/** @var string */
	protected $language;

	/** @var string */
	protected $domain;

	/** @var string */
	protected $context;

	/** @var string */
	protected $name;

	/** @var string */
	protected $value;

	/** @var int */
	protected $status;

	/** @var string */
	protected $translationPriority;

	/** @var int */
	protected $wordCount;

	/** @var int */
	protected $kind;

	/** @var int */
	protected $type;

	/** @var int[] */
	protected $sources;

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
		array $sources = []
	) {
		$this->id                  = $id;
		$this->language            = $language;
		$this->domain              = $domain;
		$this->context             = $context;
		$this->name                = $name;
		$this->value               = $value;
		$this->status              = $status;
		$this->translationPriority = $translationPriority;
		$this->wordCount           = $wordCount;
		$this->kind                = $kind;
		$this->type                = $type;
		$this->sources             = $sources;
	}

	public function getId(): int {
		return $this->id;
	}

	public function getLanguage(): string {
		return $this->language;
	}

	public function getDomain(): string {
		return $this->domain;
	}

	public function getContext(): string {
		return $this->context;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getValue(): string {
		return $this->value;
	}

	public function getStatus(): int {
		return $this->status;
	}

	public function getTranslationPriority(): string {
		return $this->translationPriority;
	}

	public function getWordCount(): int {
		return $this->wordCount;
	}

	public function getKind(): int {
		return $this->kind;
	}

	public function getType(): int {
		return $this->type;
	}

	public function getSources(): array {
		return $this->sources;
	}
}