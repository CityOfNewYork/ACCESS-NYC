<?php

namespace WPML\StringTranslation\Infrastructure\Translation\Query;

use WPML\StringTranslation\Application\Translation\Query\Dto\TranslationDetailsDto;
use WPML\StringTranslation\Application\Translation\Query\FindTranslationDetailsQueryInterface;

class FindTranslationDetailsQuery implements FindTranslationDetailsQueryInterface {

	/** @var FindTranslationDataQueryBuilder */
	private $findTranslationDataQueryBuilder;

	/** @var FindJobAndStatusDataQueryBuilder */
	private $findJobAndStatusDataQueryBuilder;

	/**
	 * @param FindTranslationDataQueryBuilder  $findTranslationDataQueryBuilder
	 * @param FindJobAndStatusDataQueryBuilder $findJobAndStatusDataQueryBuilder
	 */
	public function __construct(
		FindTranslationDataQueryBuilder  $findTranslationDataQueryBuilder,
		FindJobAndStatusDataQueryBuilder $findJobAndStatusDataQueryBuilder
	) {
		$this->findTranslationDataQueryBuilder  = $findTranslationDataQueryBuilder;
		$this->findJobAndStatusDataQueryBuilder = $findJobAndStatusDataQueryBuilder;
	}

	/**
	 * @param int[]    $stringIds
	 * @param string[] $languageCodes
	 *
	 * @return TranslationDetailsDto[]
	 */
	public function execute( array $stringIds, array $languageCodes ): array {
		global $wpdb;

		if ( count( $stringIds ) === 0 || count( $languageCodes ) === 0 ) {
			return [];
		}

		$query = $this->findTranslationDataQueryBuilder->build( $stringIds, $languageCodes );
		$res   = $wpdb->get_results( $query, ARRAY_A );
		$rids  = array_values(
			array_unique(
				array_map(
					function( $row ) {
						return (int)$row['rid'];
					},
					$res
				)
			)
		);

		if ( count( $rids ) > 0 ) {
			$query = $this->findJobAndStatusDataQueryBuilder->build( $rids );
			$dataRes = $wpdb->get_results( $query, ARRAY_A );
			foreach ( $dataRes as $data ) {
				foreach ( $res as &$row ) {
					if ( (int)$row['rid'] !== (int)$data['rid'] ) {
						continue;
					}

					foreach ( $data as $fieldName => $fieldValue ) {
						$row[ $fieldName ] = $fieldValue;
					}
				}
			}
		}

		return array_map(
			function( $row ) {
				$getIntOrNull = function( $row, $key ) {
					return is_numeric( $row[ $key ] ) ? (int) $row[ $key ] : null;
				};
				$getStringOrNull = function( $row, $key ) {
					return is_string( $row[ $key ] ) && strlen( $row[ $key ] ) > 0
						? $row[ $key ]
						: null;
				};

				return new TranslationDetailsDto(
					$row['language_code'],
					(int) $row['translation_id'],
					(int) $row['string_id'],
					$getIntOrNull( $row, 'rid' ),
					$getIntOrNull( $row, 'job_id' ),
					$getIntOrNull( $row, 'automatic' ),
					$getStringOrNull( $row, 'editor' ),
					$getStringOrNull( $row, 'translation_service' ),
					$getStringOrNull( $row, 'review_status' ),
					$getIntOrNull( $row, 'translator_id' )
				);
			},
			$res
		);
	}
}
