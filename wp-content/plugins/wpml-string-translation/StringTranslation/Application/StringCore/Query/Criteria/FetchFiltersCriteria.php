<?php

namespace WPML\StringTranslation\Application\StringCore\Query\Criteria;

class FetchFiltersCriteria {

	/** @var int|null */
	private $kind;

	/** @var int|null */
	private $type;

	/** @var int|null */
	private $source;

	/** @var string|null */
	private $domain;

	/** @var string|null */
	private $title;

	/** @var string|null */
	private $translationPriority;

	/** @var ?string */
	private $sourceLanguageCode;

	/** @var int[] */
	private $translationStatuses = [];

	public function __construct(
		int $kind                   = null,
		int $type                   = null,
		int $source                 = null,
		string $domain              = null,
		string $title               = null,
		string $translationPriority = null,
		string $sourceLanguageCode  = null,
		array  $translationStatuses = []
	) {
		$this->kind                = $kind;
		$this->type                = $type;
		$this->source              = $source;
		$this->domain              = $domain;
		$this->title               = $title;
		$this->translationPriority = $translationPriority;
		$this->sourceLanguageCode  = $sourceLanguageCode;
		$this->translationStatuses = $translationStatuses;
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

	/** @return string|null */
	public function getDomain() {
		return $this->domain;
	}

	/** @return string|null */
	public function getTitle() {
		return $this->title;
	}

	/** @return string|null */
	public function getTranslationPriority() {
		return $this->translationPriority;
	}

	/** @return ?string */
	public function getSourceLanguageCode() {
		return $this->sourceLanguageCode;
	}

	public function getTargetLanguageCode() {
		return null;
	}

	/** @return int[] */
	public function getTranslationStatuses(): array {
		return $this->translationStatuses;
	}
}