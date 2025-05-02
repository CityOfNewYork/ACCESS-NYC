<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Repository\Util;

class FilterOnlyNewFrontendStrings {

	/**
	 * @var array{
	 *     requestUrl: string,
	 *     gettextStrings: array<array{domain: string, value: string, context: string|null}>
	 * } $newData
	 * @var array{
	 *     requestUrl: string,
	 *     gettextStrings: array<array{domain: string, value: string, context: string|null}>
	 * } $existingData
	 *
	 * @return array{
	 *     requestUrl: string,
	 *     gettextStrings: array<array{domain: string, value: string, context: string|null}>
	 * }
	 */
	public function run( array $newData, array $existingData ): array {
		$filteredNewDataItems = [];

		foreach ( $newData as $newDataItem ) {
			$foundExistingDataItem = null;
			foreach ( $existingData as $existingDataItem ) {
				if ( $existingDataItem['requestUrl'] === $newDataItem['requestUrl'] ) {
					$foundExistingDataItem = $existingDataItem;
					break;
				}
			}

			if ( is_null( $foundExistingDataItem ) ) {
				$filteredNewDataItems[] = $newDataItem;
				continue;
			}

			$existingStringKeys = array_map(
				function( $string ) {
					return $string['value'] . $string['domain'] . $string['context'];
				},
				$foundExistingDataItem['gettextStrings']
			);
			$newGettextStringsForUrl = array_filter(
				$newDataItem['gettextStrings'],
				function( $string ) use ( $existingStringKeys ) {
					$stringKey = $string['value'] . $string['domain'] . $string['context'];
					return ! in_array( $stringKey, $existingStringKeys );
				}
			);

			if ( count( $newGettextStringsForUrl ) === 0 ) {
				continue;
			}

			$filteredNewDataItems[] = [
				'requestUrl'     => $newDataItem['requestUrl'],
				'gettextStrings' => array_values( $newGettextStringsForUrl ),
			];
		}

		return $filteredNewDataItems;
	}
}