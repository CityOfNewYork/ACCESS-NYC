<?php

namespace WPML\StringTranslation\Infrastructure\StringHtml\Repository;

use WPML\StringTranslation\Application\StringHtml\Repository\HtmlStringsRepositoryInterface;
use WPML\StringTranslation\Application\StringHtml\Repository\JsonStringsRepositoryInterface;
use WPML\StringTranslation\Application\StringHtml\Validator\IsExcludedHtmlStringValidatorInterface;

class JsonStringsRepository implements JsonStringsRepositoryInterface {

	/** @var HtmlStringsRepositoryInterface */
	private $htmlStringsRepository;

	/** @var IsExcludedHtmlStringValidatorInterface */
	private $isExcludedHtmlStringValidator;

	public function __construct(
		HtmlStringsRepositoryInterface         $htmlStringsRepository,
		IsExcludedHtmlStringValidatorInterface $isExcludedHtmlStringValidator
	) {
		$this->htmlStringsRepository         = $htmlStringsRepository;
		$this->isExcludedHtmlStringValidator = $isExcludedHtmlStringValidator;
	}

	/**
	 * @return string[]
	 */
	public function getAllStringsFromOutput( string $output ): array {
		$jsonStrings = $this->extractStringsFromJson( $output );
		if ( is_null( $jsonStrings ) ) {
			$htmlStrings = $this->extractStringsFromHtml( $output );
		} else {
			$htmlStrings = $this->extractHtmlStringsFromJsonString( $jsonStrings );
		}

		return $htmlStrings;
	}

	/**
	 * @return null|array
	 */
	private function extractStringsFromJson( $output ) {
		$jsonArr   = json_decode( $output, true );
		$hasErrors = json_last_error() !== JSON_ERROR_NONE;

		if ( $hasErrors ) {
			return null;
		}

		if ( ! is_array( $jsonArr ) ) {
			return null;
		}

		$strings = [];
		array_walk_recursive( $jsonArr, function( $maybeString ) use ( &$strings ) {
			$strings[] = $maybeString;
		} );

		return $strings;
	}

	private function extractHtmlStringsFromJsonString( array $jsonStrings ): array {
		$htmlStrings = [];
		foreach ( $jsonStrings as $jsonString ) {
			if ( $this->isHtmlString( (string) $jsonString ) ) {
				$jsonHtmlStrings = $this->extractStringsFromHtml( $jsonString );
				foreach ( $jsonHtmlStrings as $htmlString ) {
					$htmlStrings[] = $htmlString;
				}
				continue;
			}

			if ( ! $this->isExcludedHtmlStringValidator->validate( $jsonString ) ) {
				continue;
			}
			$htmlStrings[] = $jsonString;
		}

		return $htmlStrings;
	}

	private function isHtmlString( string $string ): bool {
		$htmlWithTags = html_entity_decode( $string );
		return $htmlWithTags !== strip_tags( $htmlWithTags );
	}

	private function extractStringsFromHtml( string $output ): array {
		return $this->htmlStringsRepository->getAllStringsFromHtml( $output );
	}
}