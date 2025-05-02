<?php

namespace WPML\StringTranslation\Infrastructure\StringPackage\Query;

use WPML\StringTranslation\Application\StringPackage\Query\Criteria\StringPackageCriteria;
use WPML\StringTranslation\Application\StringPackage\Query\Dto\StringPackageWithTranslationStatusDto;
use WPML\StringTranslation\Application\StringPackage\Query\FindStringPackagesQueryInterface;
use WPML\StringTranslation\Infrastructure\Translation\TranslationStatusesParser;

class FindStringPackagesQuery implements FindStringPackagesQueryInterface {

	/** @var \wpdb */
	private $wpdb;

	/** @var \SitePress */
	private $sitepress;

	/** @var TranslationStatusesParser */
	private $translationStatusesParser;

	/** @var QueryBuilderResolver */
	private $queryBuilderResolver;

	/** @var TranslationsQuery */
	private $translationsQuery;

	/** @var JobsQuery */
	private $jobsQuery;

	public function __construct(
		TranslationStatusesParser $translationStatusesParser,
		QueryBuilderResolver $queryBuilderResolver,
		TranslationsQuery $translationsQuery,
		JobsQuery $jobsQuery
	) {
		global $wpdb, $sitepress;
		$this->wpdb = $wpdb;
		$this->sitepress = $sitepress;
		$this->translationStatusesParser = $translationStatusesParser;
		$this->queryBuilderResolver = $queryBuilderResolver;
		$this->translationsQuery = $translationsQuery;
		$this->jobsQuery = $jobsQuery;
	}

	/**
	 * @param StringPackageCriteria $criteria
	 *
	 * @return StringPackageWithTranslationStatusDto[]
	 */
	public function execute( StringPackageCriteria $criteria ) {
		$query = $this->queryBuilderResolver->resolveFindStringPackagesQueryBuilder()->build( $criteria );

		$packages = $this->wpdb->get_results( $query, ARRAY_A );
		$packages = $this->translationsQuery->get( $packages, $criteria );
		$jobs = $this->jobsQuery->get( $packages );

		$indexedJobs = [];
		if ( $jobs ) {
			foreach( $jobs as $job ) {
				$indexedJobs[ (int) $job['rid'] ] = $job;
			}
		}

		$results = [];
		foreach ( $packages as $package ) {
			$translationStatuses = $this->translationStatusesParser->parse( $package['translation_statuses'], $indexedJobs );

			$packageDto = new StringPackageWithTranslationStatusDto(
				$package['ID'],
				$package['title'],
				$package['name'],
				1,
				$package['kind_slug'],
				$translationStatuses,
				is_numeric( $package['word_count'] ) ? (int) $package['word_count'] : 0,
				$package['translator_note']
			);
			$results[] = $packageDto;
		}

		return $results;
	}

}
