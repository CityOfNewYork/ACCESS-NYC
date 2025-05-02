<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Query;

use WPML\StringTranslation\Application\StringCore\Query\Criteria\FetchFiltersCriteria;
use WPML\StringTranslation\Application\StringCore\Query\Dto\FiltersDto;
use WPML\StringTranslation\Application\StringCore\Query\FetchFiltersQueryInterface;

class FetchFiltersQuery implements FetchFiltersQueryInterface {

	/** @var FindDomainsAndPrioritiesQueryBuilder */
	private $findDomainsAndPrioritiesQueryBuilder;

	/**
	 * @param FindDomainsAndPrioritiesQueryBuilder $findDomainsAndPrioritiesQueryBuilder
	 */
	public function __construct(
		FindDomainsAndPrioritiesQueryBuilder $findDomainsAndPrioritiesQueryBuilder
	) {
		$this->findDomainsAndPrioritiesQueryBuilder = $findDomainsAndPrioritiesQueryBuilder;
	}

	/**
	 * @param FetchFiltersCriteria $criteria
	 */
	public function execute( FetchFiltersCriteria $criteria ): FiltersDto {
		global $wpdb;

		$query = $this->findDomainsAndPrioritiesQueryBuilder->build( $criteria );
		$res = $wpdb->get_results( $query, ARRAY_A );
		$getCol = function( $data, $prop ) {
			return array_unique(
				array_map(
					function( $item ) use ( $prop ) {
						return $item[ $prop ];
					},
					$data
				)
			);
		};

		$filterData = $this->mapFiltersData(
			$getCol( $res, 'domain' ),
			$getCol( $res, 'translation_priority' )
		);

		return $filterData;
	}

	private function mapFiltersData( array $domains, array $translationPriorities ): FiltersDto {
		$rmEmptyVals = function( $items ) {
			return array_values(
				array_filter(
					$items,
					function( $item ) {
						return strlen( $item ) > 0;
					}
				)
			);
		};
		$sort = function( $a, $b ) {
			return strcasecmp( $a, $b );
		};

		$domains = array_map(
			function( $domain ) {
				return html_entity_decode( $domain, ENT_QUOTES );
			},
			$rmEmptyVals( $domains )
		);
		$translationPriorities = $rmEmptyVals( $translationPriorities );

		usort( $domains, $sort );
		usort( $translationPriorities, $sort );

		return new FiltersDto(
			$domains,
			$translationPriorities
		);
	}
}
