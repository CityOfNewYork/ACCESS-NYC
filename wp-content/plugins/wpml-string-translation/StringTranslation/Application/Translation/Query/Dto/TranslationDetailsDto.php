<?php

namespace WPML\StringTranslation\Application\Translation\Query\Dto;

final class TranslationDetailsDto {

	/** @var string */
	private $languageCode;

	/** @var int */
	private $translationId;

	/** @var int */
	private $stringId;

	/** @var int|null */
	private $rid;

	/** @var int|null */
	private $jobId;

	/** @var int|null */
	private $automatic;

	/** @var string|null */
	private $editor;

	/** @var string|null */
	private $translationService;

	/** @var string|null */
	private $reviewStatus;

	/** @var int|null */
	private $translatorId;

	/** @var int|null */
	private $editorJobId;


	/**
	 * @param string      $languageCode
	 * @param int         $translationId
	 * @param int         $stringId
	 * @param int|null    $rid
	 * @param int|null    $jobId
	 * @param int|null    $automatic
	 * @param string|null $editor
	 * @param string|null $translationService
	 * @param string|null $reviewStatus
	 * @param int|null    $translatorId
	 * @param int|null    $editorJobId
	 */
	public function __construct(
		string $languageCode,
		int $translationId,
		int $stringId,
		$rid = null,
		$jobId = null,
		$automatic = null,
		$editor = null,
		$translationService = null,
		$reviewStatus = null,
		$translatorId = null,
		$editorJobId = null
	) {
		$this->languageCode       = $languageCode;
		$this->translationId      = $translationId;
		$this->stringId           = $stringId;
		$this->rid                = $rid;
		$this->jobId              = $jobId;
		$this->automatic          = $automatic;
		$this->editor             = $editor;
		$this->translationService = $translationService;
		$this->reviewStatus       = $reviewStatus;
		$this->translatorId       = $translatorId;
		$this->editorJobId        = $editorJobId;
	}

	public function getLanguageCode(): string {
		return $this->languageCode;
	}

	public function getTranslationId(): int {
		return $this->translationId;
	}

	public function getStringId(): int {
		return $this->stringId;
	}

	/** @return int|null */
	public function getRid() {
		return $this->rid;
	}

	/** @return int|null */
	public function getJobId() {
		return $this->jobId;
	}

	/** @return int|null */
	public function getAutomatic() {
		return $this->automatic;
	}

	/** @return string|null */
	public function getEditor() {
		return $this->editor;
	}

	/** @return string|null */
	public function getTranslationService() {
		return $this->translationService;
	}

	/** @return string|null */
	public function getReviewStatus() {
		return $this->reviewStatus;
	}

	/** @return int|null */
	public function getTranslatorId() {
		return $this->translatorId;
	}

	/** @return int|null */
	public function getEditorJobId() {
		return $this->editorJobId;
	}
}
