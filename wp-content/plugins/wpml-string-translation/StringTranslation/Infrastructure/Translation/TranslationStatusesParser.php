<?php

namespace WPML\StringTranslation\Infrastructure\Translation;

use WPML\StringTranslation\Application\Translation\Query\Dto\TranslationStatusDto;

class TranslationStatusesParser {

	public function parse( string $translationStatusesRawString, array $ridIndexedJobsArray = [] ): array {
		$translationStatuses = [];
		foreach (
			array_filter( explode( ';', $translationStatusesRawString ) ) as $row
		) {
			// Split the row by comma and map the values to an associative array
			$values = [];
			foreach ( explode( ',', $row ) as $pair ) {
				$fields = explode( ':', $pair );
				if ( count( $fields ) === 2 ) {
					$values[ trim( $fields[0] ) ] = trim( $fields[1] );
				}
			}

			$langCode = $values['languageCode'] ?? null;
			if ( ! $langCode ) {
				continue;
			}

			if ( ! isset( $values['reviewStatus'] ) ) {
				$values['reviewStatus'] = 'NULL';
			}
			if ( ! isset( $values['jobId'] ) ) {
				$values['jobId'] = 'NULL';
			}
			if ( ! isset( $values['automatic'] ) ) {
				$values['automatic'] = 'NULL';
			}

			$status = (int) ( $values['status'] ?? ICL_TM_NOT_TRANSLATED ); // Status as integer

			$rid = isset( $values['rid'] ) ? (int) $values['rid'] : 0;
			if ( $rid > 0 && isset( $ridIndexedJobsArray[ $rid ] ) ) {
				$job                          = $ridIndexedJobsArray[ $rid ];
				$values['jobId']              = $job['job_id'];
				$values['automatic']          = $job['automatic'];
				$values['translationService'] = $job['translation_service'];
				$values['editor']             = $job['editor'];
				$values['reviewStatus']       = $job['review_status'];
				$values['translated']         = $job['translated'];
				$values['translatorId']       = $job['translator_id'];

			}

			$reviewStatus       = isset( $job['reviewStatus'] ) && $values['reviewStatus'] === 'NULL' ? null : $values['reviewStatus'];
			$jobId              = isset( $values['jobId'] ) && $values['jobId'] !== 'NULL' ? (int) $values['jobId'] : null;
			$isTranslated       = isset( $values['translated'] ) && $values['translated'] !== 'NULL' ? (bool) $values['translated'] : false;
			$automatic          = isset( $values['automatic'] ) && $values['automatic'] !== 'NULL' && (int) $values['automatic'] > 0;
			$translationService = $values['translationService'] ?? 'local';
			$editor             = $values['editor'] ?? null;
			$translatorId       = isset( $values['translatorId'] ) && $values['translatorId'] !== 'NULL' ? (int) $values['translatorId'] : null;

			$method = null;
			if ( $status === ICL_TM_DUPLICATE ) {
				$method = 'duplicate';
			} elseif ( $translationService !== 'local' && $translationService !== 'NULL' ) {
				$method = 'translation-service';
			} elseif ( $automatic ) {
				$method = 'automatic';
			} elseif ( $jobId ) {
				$method = 'local-translator';
			}

			$editor = $this->parseEditor( $editor );

			// Construct the nested array
			$translationStatuses[ $langCode ] = new TranslationStatusDto(
				$status,
				$reviewStatus,
				$jobId,
				$method,
				$editor,
				$isTranslated,
				$translatorId
			);
		}

		return $translationStatuses;
	}

	/**
	 * @param string|null $editor
	 *
	 * @return string|null
	 */
	private function parseEditor( $editor ) {
		if ( $editor === 'wpml' ) {
			$editor = 'classic';
		} elseif ( $editor === 'wp' ) {
			$editor = 'wordpress';
		} elseif ( ! $editor || $editor === 'NULL' ) {
			$editor = null;
		}

		return $editor;
	}
}
