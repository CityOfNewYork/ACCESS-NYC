<?php

namespace WPML\StringTranslation\Application\Translation\Query\Dto;

use WPML\PHP\ConstructableFromArrayInterface;
use WPML\PHP\ConstructableFromArrayTrait;

/**
 * @phpstan-type ReviewStatusString = 'NEEDS_REVIEW'|'EDITING'|'ACCEPTED'|null
 * @phpstan-type MethodString = 'duplicate'|'translation-service'|'automatic'|'manual'|null
 * @phpstan-type EditorString = 'classic'|'wordpress'|'ate'|'none'|null
 *
 * @phpstan-type TranslationStatusDtoArray = array{
 *   status: int,
 *   reviewStatus: ReviewStatusString,
 *   jobId: int|null,
 *   method: MethodString,
 *   editor: EditorString
 * }
 */
final class TranslationStatusDto {

	/** @var int */
	private $status;

	/** @var ReviewStatusString */
	private $reviewStatus;

	/** @var int|null */
	private $jobId;

	/** @var MethodString */
	private $method;

	/** @var EditorString */
	private $editor;

	/** @var bool */
	private $isTranslated;

	/** @var int|null	*/
  private $translatorId;


	public function __construct(
	int $status,
	string $reviewStatus = null,
	int $jobId = null,
	string $method = null,
	string $editor = null,
	bool $isTranslated = false,
	int $translatorId = null
	) {
		$allowedReviewStatus = [ 'NEEDS_REVIEW', 'EDITING', 'ACCEPTED' ];
		$allowedMethod       = [ 'duplicate', 'translation-service', 'automatic', 'manual', 'local-translator' ];
		$allowedEditor       = [ 'classic', 'wordpress', 'ate', 'none' ];

		$this->status       = $status;
		$this->reviewStatus = in_array( $reviewStatus, $allowedReviewStatus, true ) ? $reviewStatus : null;
		$this->jobId        = $jobId;
		$this->method       = in_array( $method, $allowedMethod, true ) ? $method : null;
		$this->editor       = in_array( $editor, $allowedEditor, true ) ? $editor : null;
		$this->isTranslated = $isTranslated;
		$this->translatorId = $translatorId;
	}


	public function getStatus(): int {
		return $this->status;
	}


	/**
	 * @return ReviewStatusString
	 */
	public function getReviewStatus() {
		return $this->reviewStatus;
	}


	/**
	 * @return int|null
	 */
	public function getJobId() {
		return $this->jobId;
	}


	/**
	 * @return MethodString
	 */
	public function getMethod() {
		return $this->method;
	}


	/**
	 * @return EditorString
	 */
	public function getEditor() {
		return $this->editor;
	}

	/**
	 * @return int|null
	 */
	public function getIsTranslated() {
		return $this->isTranslated;
	}

	/**
	 * @return int|null
	 */
	public function getTranslatorId() {
		return $this->translatorId;
	}

	/**
	 * @return TranslationStatusDtoArray
	 */
	public function toArray(): array {
		return [
			'status'       => $this->status,
			'reviewStatus' => $this->reviewStatus,
			'jobId'        => $this->jobId,
			'method'       => $this->method,
			'editor'       => $this->editor,
			'isTranslated' => $this->isTranslated,
			'translatorId' => $this->translatorId,
		];
	}


}
