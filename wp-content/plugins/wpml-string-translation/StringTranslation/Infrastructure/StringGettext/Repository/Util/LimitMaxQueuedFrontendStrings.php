<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Repository\Util;

class LimitMaxQueuedFrontendStrings {

	/**
	 * @var array{
	 *     requestUrl: string,
	 *     gettextStrings: array<array{domain: string, value: string, context: string|null}>
	 * } $data
	 * @var array{
	 *     requestUrl: string,
	 *     gettextStrings: array<array{domain: string, value: string, context: string|null}>
	 * } $existingData
	 * @var int $maxQueuedFrontendStringsCount
	 *
	 * @return array{
	 *     requestUrl: string,
	 *     gettextStrings: array<array{domain: string, value: string, context: string|null}>
	 * }
	 */
	public function run( array $data, array $existingData, int $maxQueuedFrontendStringsCount ): array {
		$totalStringsCount = 0;
		foreach ( $existingData as $entry ) {
			$totalStringsCount += count( $entry['gettextStrings'] );
		}

		$allowedStringsLeftToQueue = $maxQueuedFrontendStringsCount - $totalStringsCount;

		if ( $allowedStringsLeftToQueue <= 0 ) {
			return [];
		}

		$allowedData = [];
		foreach ( $data as $item ) {
			$stringsCount = count( $item['gettextStrings'] );
			if ( $allowedStringsLeftToQueue < $stringsCount ) {
				$stringsCount = $allowedStringsLeftToQueue;
			}

			$allowedStringsLeftToQueue -= $stringsCount;
			$allowedData[] = [
				'requestUrl'     => $item['requestUrl'],
				'gettextStrings' => array_slice( $item['gettextStrings'], 0, $stringsCount ),
			];

			if ( $allowedStringsLeftToQueue <= 0 ) {
				break;
			}
		}

		return $allowedData;
	}
}