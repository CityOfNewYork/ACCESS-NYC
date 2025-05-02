<?php

namespace WPML\StringTranslation\Application\StringCore\Query\Criteria;

class SearchCriteria {

	/** @var int|null */
	private $kind;

	/** @var int|null */
	private $type;

	/** @var int|null */
	private $source;

	/** @var ?string */
	private $domain;

	/** @var ?string */
	private $title;

	/** @var ?string */
	private $translationPriority;

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

	/** @var array{by: string, order: string} | null */
	private $sorting;

	/** @var int[] */
	private $ids = [];

	public function __construct(
		int $kind = null,
		int $type = null,
		int $source = null,
		string $domain = null,
		string $title = null,
		string $translationPriority = null,
		string $sourceLanguageCode = null,
		string $targetLanguageCode = null,
		array $translationStatuses = [],
		int $limit = 10,
		int $offset = 0,
		array $sorting = null
	) {
		$this->kind                = $kind;
		$this->type                = $type;
		$this->source              = $source;
		$this->domain              = $domain;
		$this->title               = $title;
		$this->translationPriority = $translationPriority;
		$this->sourceLanguageCode  = $sourceLanguageCode;
		$this->targetLanguageCode  = $targetLanguageCode;
		$this->translationStatuses = $translationStatuses;
		$this->limit               = $limit;
		$this->offset              = $offset;
		$this->sorting             = $sorting;
	}

	/** @return int|null */
	public function getKind() {
		return $this->kind;
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
	public function getTranslationPriority() {
		return $this->translationPriority;
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

	/**
	 * @param int[]|array<int, array> $strings
	 */
	public function addIds( array $strings ) {
		$this->ids = array_map(
			function( $string ) {
				return is_array( $string ) ? $string['string_id'] : $string;
			},
			$strings
		);
	}

	public function getIds(): array {
		return $this->ids;
	}
}
