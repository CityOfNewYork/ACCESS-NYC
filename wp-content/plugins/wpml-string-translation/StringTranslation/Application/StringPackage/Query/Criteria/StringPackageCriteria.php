<?php

namespace WPML\StringTranslation\Application\StringPackage\Query\Criteria;



class StringPackageCriteria {

	/** @var string */
	private $type;

	/** @var ?string */
	private $title;

	/** @var ?string */
	private $sourceLanguageCode;

	/** @var ?string */
	private $targetLanguageCode;

	/** @var int[] */
	private $translationStatuses = [];

	/** @var int */
	private $limit = 10;

	/** @var int */
	private $offset = 0;

	/** @var array{by: string, order: string}|null */
	private $sorting;

	public function __construct(
		string $type = null,
		string $title = null,
		string $sourceLanguageCode = null,
		string $targetLanguageCode = null,
		array $translationStatuses = [],
		int $limit = 10,
		int $offset = 0,
		array $sorting = null
	) {
		$this->type                = $type;
		$this->title               = $title;
		$this->sourceLanguageCode  = $sourceLanguageCode;
		$this->targetLanguageCode  = $targetLanguageCode;
		$this->translationStatuses = $translationStatuses;
		$this->limit               = $limit;
		$this->offset              = $offset;
		$this->sorting             = $sorting;
	}

	/** @return int|null */
	public function getType() {
		return $this->type;
	}

	/** @return int|null */
	public function getSource() {
		return $this->source;
	}

	/** @return ?string */
	public function getDomain() {
		return $this->domain;
	}

	/** @return ?string */
	public function getTitle() {
		return $this->title;
	}

	/** @return ?string */
	public function getSourceLanguageCode() {
		return $this->sourceLanguageCode;
	}

	/** @return ?string */
	public function getTargetLanguageCode() {
		return $this->targetLanguageCode;
	}

	/** @return int[] */
	public function getTranslationStatuses(): array {
		return $this->translationStatuses;
	}

	public function getLimit(): int {
		return $this->limit;
	}

	public function getOffset(): int {
		return $this->offset;
	}

	/**
	 * @return array{by: string, order: string}|null
	 */
	public function getSorting() {
		return $this->sorting;
	}
}
