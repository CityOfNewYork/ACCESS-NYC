<?php

namespace WPML\StringTranslation\Application\StringPackage\Query\Criteria;

class SearchPopulatedKindsCriteria {


	/**
	 * @var string[]
	 */
	private $itemSectionIds = [];

	/** @var string|null */
	private $publicationStatus;

	/** @var string */
	private $sourceLanguageCode;

	/** @var string|null */
	private $targetLanguageCode;

	/** @var int[] */
	private $translationStatuses = [];


	/**
	 * Constructor for SearchPopulatedKindsCriteria
	 *
	 * @param string[] $itemSectionIds Array of item section IDs to search in.
	 * @param string|null $publicationStatus Optional publication status to filter by.
	 * @param string $sourceLanguageCode The source language code.
	 * @param string|null $targetLanguageCode Optional target language code.
	 * @param int[] $translationStatuses Array of translation statuses to filter by.
	 */
	public function __construct(
		array $itemSectionIds = [],
		?string $publicationStatus = null,
		string $sourceLanguageCode = '',
		?string $targetLanguageCode = null,
		array $translationStatuses = []
	) {
		$this->sourceLanguageCode  = $sourceLanguageCode;
		$this->itemSectionIds     = $itemSectionIds;
		$this->publicationStatus   = $publicationStatus;
		$this->targetLanguageCode  = $targetLanguageCode;
		$this->translationStatuses = $translationStatuses;
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
	public function getTranslationStatuses() {
		return $this->translationStatuses;
	}


	/** @return string[] */
	public function getItemSectionIds() {
		return $this->itemSectionIds;
	}


	/**
	 * @return string[]
	 */
	public function getStringPackageTypeIds(): array {
		return array_map(
			function ( $itemSectionId ) {
				return str_replace( 'stringPackage/', '', $itemSectionId );
			},
			array_filter( $this->itemSectionIds,
				function ( $itemSectionId ) {
					return strpos( $itemSectionId, 'stringPackage/' ) === 0;
				}
			)
		);
	}


}
